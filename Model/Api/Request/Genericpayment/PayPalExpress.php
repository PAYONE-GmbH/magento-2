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

namespace Payone\Core\Model\Api\Request\Genericpayment;

use Magento\Quote\Model\Quote;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Model\Methods\PaypalV2;
use Payone\Core\Model\PayoneConfig;

/**
 * Class for the PAYONE Server API request genericpayment - "setexpresscheckout" and "getexpresscheckoutdetails"
 */
class PayPalExpress extends Base
{
    /**
     * Invoice generator
     *
     * @var \Payone\Core\Model\Api\Invoice
     */
    protected $invoiceGenerator;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Helper\Toolkit             $toolkitHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Payone\Core\Model\Api\Invoice          $invoiceGenerator
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Payone\Core\Model\Api\Invoice $invoiceGenerator
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $toolkitHelper, $apiLog, $customerHelper);
        $this->invoiceGenerator = $invoiceGenerator;
    }

    /**
     * Send request to PAYONE Server-API with
     * request-type "genericpayment"
     *
     * @param Quote        $oQuote
     * @param PayoneMethod $oPayment
     * @param string|bool  $sWorkorderId
     * @return array Response
     */
    public function sendRequest(Quote $oQuote, PayoneMethod $oPayment, $sWorkorderId = false)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('clearingtype', $oPayment->getClearingtype());
        $this->addParameter('wallettype', $oPayment->getWallettype());
        $this->addParameter('narrative_text', 'Test');

        $this->addParameter('amount', number_format($this->apiHelper->getQuoteAmount($oQuote), 2, '.', '') * 100); // add price to request
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromQuote($oQuote)); // add currency to request

        if ($sWorkorderId !== false) {
            $this->addParameter('workorderid', $sWorkorderId);
            $this->addParameter('add_paydata[action]', 'getexpresscheckoutdetails');
        } else {
            $this->addParameter('add_paydata[action]', 'setexpresscheckout');

            if ($oPayment instanceof PaypalV2) {
                $this->addParameter('add_paydata[payment_action]', $oPayment->getAuthorizationMode() == PayoneConfig::REQUEST_TYPE_AUTHORIZATION ? 'Capture' : 'Authorize'); # Is either Capture (for Authorization call) or Authorize (for preauthorization call)
            }

            if ($this->apiHelper->isInvoiceDataNeeded($oPayment)) {
                $this->invoiceGenerator->addProductInfo($this, $oQuote);
            }
        }

        $this->addRedirectUrls($oPayment);

        return $this->send($oPayment);
    }
}
