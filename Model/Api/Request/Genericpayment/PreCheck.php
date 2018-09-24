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

use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Quote\Model\Quote;

/**
 * Class for the PAYONE Server API request genericpayment - "pre_check"
 */
class PreCheck extends Base
{
    /**
     * Send request to PAYONE Server-API with request-type "genericpayment" and action "pre_check"
     *
     * @param  PayoneMethod $oPayment payment object
     * @param  Quote        $oQuote   order object
     * @param  float|bool   $dAmount  order sum amount
     * @param  string|bool  $sBirthday
     * @param  string|bool  $sEmail
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, Quote $oQuote, $dAmount = false, $sBirthday = false, $sEmail = false)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'pre_check');

        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('api_version', '3.10');

        $this->addParameter('clearingtype', $oPayment->getClearingtype());
        $this->addParameter('financingtype', $oPayment->getSubType());
        $this->addParameter('add_paydata[payment_type]', $oPayment->getLongSubType());

        if ($dAmount === false) {
            $dAmount = $this->apiHelper->getQuoteAmount($oQuote);
        }
        $this->addParameter('amount', number_format($dAmount, 2, '.', '') * 100); // add price to request
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromQuote($oQuote)); // add currency to request

        if ($sEmail === false) {
            $sEmail = $oQuote->getCustomerEmail();
        }
        $this->addParameter('email', $sEmail);

        #if ($sBirthday === false && $oPayment->getData('info_instance')) {
        if ($oPayment->getData('info_instance')) {
            $sBirthday = $oPayment->getInfoInstance()->getAdditionalInformation('dateofbirth');
        }
        if ($sBirthday) {
            $this->addParameter('birthday', $sBirthday);
        }

        $oBilling = $oQuote->getBillingAddress();
        $this->addAddress($oBilling);

        if ($oBilling->getCountryId() == 'NL') {
            $sTelephone = $oBilling->getTelephone();
            if (empty($sTelephone)) {
                $sTelephone = $oPayment->getInfoInstance()->getAdditionalInformation('telephone');
            }
            $this->addParameter('telephone', $sTelephone);
        }

        $this->addParameter('language', $this->shopHelper->getLocale());

        $sIp = $this->environmentHelper->getRemoteIp(); // get remote IP
        if ($sIp != '') {// is IP not empty
            $this->addParameter('ip', $sIp); // add IP address to the request
        }

        if ($oPayment->getData('info_instance')) {
            $sTradeRegistryNumber = $oPayment->getInfoInstance()->getAdditionalInformation('trade_registry_number');
            if ($sTradeRegistryNumber) {
                $this->addParameter('add_paydata[b2b]', 'yes');
                $this->addParameter('add_paydata[company_trade_registry_number]', $sTradeRegistryNumber);
            }
        }

        return $this->send($oPayment);
    }
}
