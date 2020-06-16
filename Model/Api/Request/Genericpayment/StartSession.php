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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Api\Request\Genericpayment;

use Magento\Quote\Model\Quote;
use Payone\Core\Model\Methods\PayoneMethod;

/**
 * Class for the PAYONE Server API request genericpayment - "start_session"
 */
class StartSession extends Base
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
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Payone\Core\Model\Api\Invoice          $invoiceGenerator
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Payone\Core\Model\Api\Invoice $invoiceGenerator
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog, $customerHelper);
        $this->customerHelper = $customerHelper;
        $this->invoiceGenerator = $invoiceGenerator;
    }

    /**
     * Send request to PAYONE Server-API with
     * request-type "genericpayment"
     *
     * @param Quote         $oQuote
     * @param PayoneMethod  $oPayment
     * @param double        $dShippingCosts
     * @param string        $sCustomerEmail
     * @return array Response
     */
    public function sendRequest(Quote $oQuote, PayoneMethod $oPayment, $dShippingCosts, $sCustomerEmail)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'start_session');
        $this->addParameter('api_version', '3.10');
        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account

        $this->addParameter('amount', number_format($this->apiHelper->getQuoteAmount($oQuote), 2, '.', '') * 100); // add price to request
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromQuote($oQuote)); // add currency to request

        $oBilling = $oQuote->getBillingAddress();
        $this->addAddress($oBilling);
        if ($oBilling->getCompany()) {
            $this->addParameter('add_paydata[organization_entity_type]', 'OTHER');
            $this->addParameter('add_paydata[organization_registry_id]', '');
        }

        $oShipping = $oQuote->getShippingAddress();
        if ($oShipping) {
            $this->addAddress($oShipping, true);
            $this->addParameter('add_paydata[shipping_email]', $sCustomerEmail);
            $this->addParameter('add_paydata[shipping_title]', '');
            $this->addParameter('add_paydata[shipping_telephonenumber]', '');
        }

        $this->addParameter('clearingtype', 'fnc');
        $this->addParameter('financingtype', $oPayment->getSubType());

        $this->invoiceGenerator->addProductInfo($this, $oQuote, false, false, $dShippingCosts);

        return $this->send($oPayment);
    }
}
