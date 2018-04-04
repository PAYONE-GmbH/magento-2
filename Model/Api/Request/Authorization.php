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

namespace Payone\Core\Model\Api\Request;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Sales\Model\Order;

/**
 * Class for the PAYONE Server API request "(pre)authorization"
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
class Authorization extends AddressRequest
{
    /**
     * Invoice generator
     *
     * @var \Payone\Core\Model\Api\Invoice
     */
    protected $invoiceGenerator;

    /**
     * Checkout session object
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
     protected $toolkitHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Payone\Core\Model\Api\Invoice          $invoiceGenerator
     * @param \Magento\Checkout\Model\Session         $checkoutSession
     * @param \Payone\Core\Helper\Toolkit             $toolkitHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Payone\Core\Model\Api\Invoice $invoiceGenerator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Helper\Toolkit $toolkitHelper
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog, $customerHelper);
        $this->invoiceGenerator = $invoiceGenerator;
        $this->checkoutSession = $checkoutSession;
        $this->toolkitHelper = $toolkitHelper;
    }

    /**
     * Send request to PAYONE Server-API with
     * request-type "authorization" or "preauthorization"
     *
     * @param  PayoneMethod $oPayment payment object
     * @param  Order        $oOrder   order object
     * @param  float        $dAmount  order sum amount
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, Order $oOrder, $dAmount)
    {
        $this->setOrderId($oOrder->getRealOrderId()); // save order id to object for later use

        $this->addParameter('request', $oPayment->getAuthorizationMode()); // add request type
        $this->addParameter('mode', $oPayment->getOperationMode()); // add mode ( live or test )
        $this->addParameter('customerid', $oOrder->getCustomerId()); // add customer id
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // add sub account id
        $this->setAuthorizationParameters($oPayment, $oOrder, $dAmount); // set authorization params

        $aResponse = $this->send($oPayment); // send request to PAYONE Server API

        $this->apiHelper->addPayoneOrderData($oOrder, $this->getParameters(), $aResponse); // add payone data to order

        return $aResponse;
    }

    /**
     * Add user parameters
     *
     * @param  PayoneMethod $oPayment
     * @param  Order        $oOrder
     * @return void
     */
    protected function setUserParameters(PayoneMethod $oPayment, Order $oOrder)
    {
        $oQuote = $this->checkoutSession->getQuote(); // get quote from session
        $oCustomer = $oQuote->getCustomer(); // get customer object from quote
        $this->addUserDataParameters($oOrder->getBillingAddress(), $oPayment, $oCustomer->getGender(), $oOrder->getCustomerEmail(), $oCustomer->getDob());

        $oShipping = $oOrder->getShippingAddress(); // get shipping address from order
        if ($oShipping) {// shipping address existing?
            $this->addAddress($oShipping, true); // add regular shipping address
        } elseif ($oPayment->getCode() == PayoneConfig::METHOD_PAYDIREKT || ($oPayment->getCode() == PayoneConfig::METHOD_PAYPAL && $this->shopHelper->getConfigParam('bill_as_del_address', PayoneConfig::METHOD_PAYPAL, 'payone_payment'))) {
            $this->addAddress($oOrder->getBillingAddress(), true); // add billing address as shipping address
        }
    }

    /**
     * Set the parameters needed for the authorization requests
     *
     * @param  PayoneMethod $oPayment
     * @param  Order        $oOrder
     * @param  float        $dAmount
     * @return void
     */
    protected function setAuthorizationParameters(PayoneMethod $oPayment, Order $oOrder, $dAmount)
    {
        $sRefNr = $this->shopHelper->getConfigParam('ref_prefix').$oOrder->getIncrementId(); // ref_prefix to prevent duplicate refnumbers in testing environments
        $sRefNr = $oPayment->formatReferenceNumber($sRefNr); // some payment methods have refnr regulations
        $this->addParameter('reference', $sRefNr); // add ref-nr to request

        $this->addParameter('amount', number_format($dAmount, 2, '.', '') * 100); // add price to request
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromOrder($oOrder)); // add currency to request

        if ($this->shopHelper->getConfigParam('transmit_ip') == '1') {// is IP transmission needed?
            $sIp = $this->environmentHelper->getRemoteIp(); // get remote IP
            if ($sIp != '') {// is IP not empty
                $this->addParameter('ip', $sIp); // add IP address to the request
            }
        }
        $this->setUserParameters($oPayment, $oOrder); // add user data - addresses etc.
        $this->setPaymentParameters($oPayment, $oOrder); // add payment specific parameters

        if ($this->apiHelper->isInvoiceDataNeeded($oPayment)) {
            $this->invoiceGenerator->addProductInfo($this, $oOrder); // add invoice parameters
        }
    }

    /**
     * Set payment-specific parameters
     *
     * @param  PayoneMethod $oPayment
     * @param  Order        $oOrder
     * @return void
     */
    protected function setPaymentParameters(PayoneMethod $oPayment, Order $oOrder)
    {
        $this->addParameter('clearingtype', $oPayment->getClearingtype()); // add payment type to request
        $sNarrativeText = $this->toolkitHelper->getNarrativeText($oOrder, $oPayment);
        if (!empty($sNarrativeText)) {// narrative text existing?
            $this->addParameter('narrative_text', $sNarrativeText); // add narrative text parameter
        }
        $aPaymentParams = $oPayment->getPaymentSpecificParameters($oOrder); // get payment params specific to the payment type
        $this->aParameters = array_merge($this->aParameters, $aPaymentParams); // merge payment params with other params
        if ($oPayment->needsRedirectUrls() === true) {// does the used payment type need redirect urls?
            $this->addRedirectUrls($oPayment); // add needed redirect urls
        }
    }
}
