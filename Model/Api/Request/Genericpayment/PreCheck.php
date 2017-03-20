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
use Magento\Sales\Model\Order;
use Locale;

/**
 * Class for the PAYONE Server API request genericpayment - "pre_check"
 */
class PreCheck extends Base
{
    /**
     * Send request to PAYONE Server-API with request-type "genericpayment" and action "pre_check"
     *
     * @param  PayoneMethod $oPayment payment object
     * @param  Order        $oOrder   order object
     * @param  float        $dAmount  order sum amount
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, Order $oOrder, $dAmount)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'pre_check');

        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('api_version', '3.10');

        $this->addParameter('clearingtype', $oPayment->getClearingtype());
        $this->addParameter('financingtype', $oPayment->getSubType());
        $this->addParameter('add_paydata[payment_type]', $oPayment->getLongSubType());

        $this->addParameter('amount', number_format($dAmount, 2, '.', '') * 100);
        $this->addParameter('currency', $oOrder->getOrderCurrencyCode());

        $this->addParameter('email', $oOrder->getCustomerEmail());
        $this->addParameter('birthday', $oPayment->getInfoInstance()->getAdditionalInformation('dateofbirth'));

        $oBilling = $oOrder->getBillingAddress();
        $this->addAddress($oBilling);

        if ($oBilling->getCountryId() == 'NL') {
            $sTelephone = $oBilling->getTelephone();
            if (empty($sTelephone)) {
                $sTelephone = $oPayment->getInfoInstance()->getAdditionalInformation('telephone');
            }
            $this->addParameter('telephone', $sTelephone);
        }

        $this->addParameter('language', Locale::getPrimaryLanguage(Locale::getDefault()));

        $sIp = $this->environmentHelper->getRemoteIp(); // get remote IP
        if ($sIp != '') {// is IP not empty
            $this->addParameter('ip', $sIp); // add IP address to the request
        }

        $sTradeRegistryNumber = $oPayment->getInfoInstance()->getAdditionalInformation('trade_registry_number');
        if ($sTradeRegistryNumber) {
            $this->addParameter('add_paydata[b2b]', 'yes');
            $this->addParameter('add_paydata[company_trade_registry_number]', $sTradeRegistryNumber);
        }

        if ($oPayment->hasCustomConfig()) {// if payment type doesnt use the global settings
            $this->addCustomParameters($oPayment); // add custom connection settings
        }

        return $this->send();
    }
}
