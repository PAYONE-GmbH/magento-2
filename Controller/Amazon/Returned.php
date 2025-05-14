<?php

/**
 * PAYONE Magento 2 Connector is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PAYONE Magento 2 Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PAYONE Magento 2 Connector. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Controller\Amazon;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Payone\Core\Model\PayoneConfig;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

class Returned extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE checkout helper
     *
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Order
     */
    protected $orderHelper;

    /**
     * Amazon Pay payment model
     *
     * @var \Payone\Core\Model\Methods\AmazonPayV2
     */
    protected $amazonPayment;

    /**
     * PAYONE GetCheckoutSession request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\GetCheckoutSession
     */
    protected $getCheckoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Payone\Core\Helper\Checkout $checkoutHelper
     * @param \Payone\Core\Helper\Order $orderHelper
     * @param \Payone\Core\Model\Methods\AmazonPayV2 $amazonPayment
     * @param \Payone\Core\Model\Api\Request\Genericpayment\GetCheckoutSession $getCheckoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Helper\Checkout $checkoutHelper,
        \Payone\Core\Helper\Order $orderHelper,
        \Payone\Core\Model\Methods\AmazonPayV2 $amazonPayment,
        \Payone\Core\Model\Api\Request\Genericpayment\GetCheckoutSession $getCheckoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->orderHelper = $orderHelper;
        $this->amazonPayment = $amazonPayment;
        $this->getCheckoutSession = $getCheckoutSession;
    }

    /**
     * @param  string $sWorkorderId
     * @return void
     */
    protected function handleReturn($sWorkorderId)
    {
        $oQuote = $this->checkoutSession->getQuote();
        $aResponse = $this->getCheckoutSession->sendRequest($this->amazonPayment, $oQuote, $sWorkorderId);
        if (!isset($aResponse['status']) || $aResponse['status'] != 'OK') {
            throw new \Exception('Could not get Amazon Pay checkout session. Status: '.$aResponse['status']);
        }

        $oQuote = $this->orderHelper->updateAddresses($oQuote, $aResponse, true);

        // Generate hash of the delivered addresses for later comparison to prevent address fraud
        $this->checkoutSession->setPayoneQuoteAddressHash($this->checkoutHelper->getQuoteAddressHash($oQuote));
        $this->checkoutSession->setPayoneExpressAddressResponse($aResponse);

        if ($this->checkoutHelper->getCurrentCheckoutMethod($oQuote) == Onepage::METHOD_GUEST) {
            $oQuote->setCustomerId(null)
                ->setCustomerEmail($oQuote->getBillingAddress()->getEmail())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        }

        $oPayment = $oQuote->getPayment();
        $oPayment->setMethod(PayoneConfig::METHOD_AMAZONPAYV2);

        $oQuote->setPayment($oPayment);
        $oQuote->setInventoryProcessed(false);
        $oQuote->collectTotals()->save();
    }

    /**
     * Redirect to payment-provider or to success page
     *
     * @return Redirect
     */
    public function execute()
    {
        $sWorkorderId = $this->checkoutSession->getPayoneWorkorderId();
        if ($sWorkorderId) {
            try {
                $this->handleReturn($sWorkorderId);

                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('payone/onepage/review');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('An error occured during the Amazon Pay transaction.')
                );
            }
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/cart');
    }
}
