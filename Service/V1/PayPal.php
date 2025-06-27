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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Service\V1;

use Payone\Core\Api\PayPalInterface;
use Payone\Core\Model\PayoneConfig;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

/**
 * Web API model for the PAYONE PayPal express
 */
class PayPal implements PayPalInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Api\Data\PayPalResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Request object
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress
     */
    protected $paypalRequest;

    /**
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * Constructor.
     *
     * @param \Payone\Core\Api\Data\PayPalResponseInterfaceFactory        $responseFactory
     * @param \Magento\Checkout\Model\Session                             $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress $paypalRequest
     * @param \Payone\Core\Helper\Checkout                                $checkoutHelper
     * @param \Magento\Payment\Helper\Data                                $dataHelper
     */
    public function __construct(
        \Payone\Core\Api\Data\PayPalResponseInterfaceFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress $paypalRequest,
        \Payone\Core\Helper\Checkout $checkoutHelper,
        \Magento\Payment\Helper\Data $dataHelper
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->paypalRequest = $paypalRequest;
        $this->checkoutHelper = $checkoutHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Trigger PayPal Express v2 process
     *
     * @param  string $cartId
     * @return \Payone\Core\Service\V1\Data\PayPalResponse
     */
    public function startPayPalExpress($cartId)
    {
        $blSuccess = false;
        $oResponse = $this->responseFactory->create();

        $oQuote = $this->checkoutSession->getQuote();

        $oPayment = $oQuote->getPayment();
        $oPayment->setMethod(PayoneConfig::METHOD_PAYPALV2);

        $oQuote->setPayment($oPayment);
        $oQuote->collectTotals();
        $oQuote->save();

        $oMethodInstance = $this->dataHelper->getMethodInstance(PayoneConfig::METHOD_PAYPALV2);
        $oMethodInstance->setMethodInstance($oQuote->getPayment());

        $aResponse = $this->paypalRequest->sendRequest($oQuote, $oMethodInstance);
        if (isset($aResponse['status'], $aResponse['workorderid'], $aResponse['add_paydata[orderId]']) && $aResponse['status'] == 'REDIRECT') {
            $blSuccess = true;

            $oResponse->setData('orderId', $aResponse['add_paydata[orderId]']);

            if (!empty($aResponse['workorderid'])) {
                $this->checkoutSession->setIsPayonePayPalExpress(true);
                $this->checkoutSession->setPayoneWorkorderId($aResponse['workorderid']);
                $this->checkoutSession->setPayoneQuoteComparisonString($this->checkoutHelper->getQuoteComparisonString($oQuote));
            }
        }

        if (isset($aResponse['status'], $aResponse['customermessage']) && $aResponse['status'] == 'ERROR') {
            $oResponse->setData('errormessage', $aResponse['customermessage']);
        }

        $oResponse->setData('success', $blSuccess);
        return $oResponse;
    }
}
