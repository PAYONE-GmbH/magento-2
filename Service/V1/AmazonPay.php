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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Service\V1;

use Payone\Core\Api\AmazonPayInterface;
use Payone\Core\Model\PayoneConfig;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

/**
 * Web API model for the PAYONE addresscheck
 */
class AmazonPay implements AmazonPayInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Api\Data\AmazonPayResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Object of getconfiguration request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration
     */
    protected $getConfiguration;

    /**
     * Object of getorderreferencedetails request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails
     */
    protected $getOrderReferenceDetails;

    /**
     * Object of setorderreferencedetails request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\SetOrderReferenceDetails
     */
    protected $setOrderReferenceDetails;

    /**
     * Amazon Pay payment object
     *
     * @var \Payone\Core\Model\Methods\AmazonPay
     */
    protected $payment;

    /**
     * Cart management interface
     *
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Order
     */
    protected $orderHelper;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * URL builder object
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Block object of review page
     *
     * @var \Payone\Core\Block\Onepage\Review
     */
    protected $reviewBlock;

    /**
     * @var \Magento\Framework\App\ViewInterface
     */
    protected $view;

    /**
     * Constructor.
     *
     * @param \Payone\Core\Api\Data\AmazonPayResponseInterfaceFactory                $responseFactory
     * @param \Magento\Checkout\Model\Session                                        $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration         $getConfiguration
     * @param \Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails $getOrderReferenceDetails
     * @param \Payone\Core\Model\Methods\AmazonPay                                   $payment
     * @param \Magento\Quote\Api\CartManagementInterface                             $cartManagement
     * @param \Payone\Core\Helper\Order                                              $orderHelper
     * @param \Payone\Core\Helper\Checkout                                           $checkoutHelper
     * @param \Magento\Framework\UrlInterface                                        $urlBuilder
     * @param \Payone\Core\Block\Onepage\Review                                      $reviewBlock
     * @param \Magento\Framework\App\ViewInterface                                   $view
     */
    public function __construct(
        \Payone\Core\Api\Data\AmazonPayResponseInterfaceFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration $getConfiguration,
        \Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails $getOrderReferenceDetails,
        \Payone\Core\Model\Api\Request\Genericpayment\SetOrderReferenceDetails $setOrderReferenceDetails,
        \Payone\Core\Model\Methods\AmazonPay $payment,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Payone\Core\Helper\Order $orderHelper,
        \Payone\Core\Helper\Checkout $checkoutHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Payone\Core\Block\Onepage\Review $reviewBlock,
        \Magento\Framework\App\ViewInterface $view
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->getConfiguration = $getConfiguration;
        $this->getOrderReferenceDetails = $getOrderReferenceDetails;
        $this->setOrderReferenceDetails = $setOrderReferenceDetails;
        $this->payment = $payment;
        $this->cartManagement = $cartManagement;
        $this->orderHelper = $orderHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->urlBuilder = $urlBuilder;
        $this->reviewBlock = $reviewBlock;
        $this->view = $view;
    }

    /**
     * Get Amazon workorder_id from session or request
     *
     * @return string
     */
    protected function collectWorkorderId()
    {
        $sWorkorderId = $this->checkoutSession->getAmazonWorkorderId();
        if (empty($sWorkorderId)) {
            $aResult = $this->getConfiguration->sendRequest($this->payment, $this->checkoutSession->getQuote());
            if (isset($aResult['status']) && $aResult['status'] == 'OK' && isset($aResult['workorderid'])) {
                $sWorkorderId = $aResult['workorderid'];
                $this->checkoutSession->setAmazonWorkorderId($aResult['workorderid']);
            }
        }
        return $sWorkorderId;
    }

    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $amazonReferenceId
     * @param  string $amazonAddressToken
     * @return \Payone\Core\Service\V1\Data\AmazonPayResponse
     */
    public function getWorkorderId($amazonReferenceId, $amazonAddressToken) {
        $blSuccess = false;
        $sWorkorderId = $this->collectWorkorderId();
        if (!empty($sWorkorderId)) {
            $blSuccess = true;

            $oQuote = $this->checkoutSession->getQuote();
            $aResult = $this->getOrderReferenceDetails->sendRequest($this->payment, $oQuote, $sWorkorderId, $amazonReferenceId, $amazonAddressToken);
            if (isset($aResult['status']) && $aResult['status'] == 'OK') {
                $this->checkoutSession->setAmazonAddressToken($amazonAddressToken);
                $this->checkoutSession->setAmazonReferenceId($amazonReferenceId);

                $oQuote = $this->orderHelper->updateAddresses($oQuote, $aResult);

                $oPayment = $oQuote->getPayment();
                $oPayment->setMethod(PayoneConfig::METHOD_AMAZONPAY);

                $oQuote->save();

                $aResult = $this->setOrderReferenceDetails->sendRequest($this->payment, $oQuote, $sWorkorderId, $amazonReferenceId, $amazonAddressToken);
                if (isset($aResult['status']) && $aResult['status'] == 'OK') {
                    if ($this->checkoutHelper->getCurrentCheckoutMethod($oQuote) == Onepage::METHOD_GUEST) {
                        $oQuote->setCustomerId(null)
                            ->setCustomerEmail($oQuote->getBillingAddress()->getEmail())
                            ->setCustomerIsGuest(true)
                            ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
                    }

                    $oQuote->setPayment($oPayment);
                    $oQuote->setInventoryProcessed(false);
                    $oQuote->collectTotals()->save();
                    $this->cartManagement->placeOrder($oQuote->getId());
                    $oQuote->setIsActive(false)->save();
                }
            }
        }

        $oResponse = $this->responseFactory->create();
        $oResponse->setData('workorderId', $sWorkorderId);
        $oResponse->setData('success', $blSuccess);
        $this->reviewBlock->setArea('frontend');
        $this->view->loadLayout('payone_onepage_review', true, true, true);

        $html = $this->view->getLayout()->getBlock('payone_onepage_review')->toHtml();

        $oResponse->setData('amazonReviewHtml', $html);
        return $oResponse;
    }
}
