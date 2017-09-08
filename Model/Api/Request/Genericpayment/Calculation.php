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

namespace Payone\Core\Model\Api\Request\Genericpayment;

use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Quote\Model\Quote;

/**
 * Class for the PAYONE Server API request genericpayment - "calculation"
 */
class Calculation extends Base
{
    /**
     * Send request to PAYONE Server-API with request-type "genericpayment" and action "calculation"
     *
     * @param  PayoneMethod $oPayment payment object
     * @param  Quote        $oQuote   order object
     * @param  float        $dAmount  order sum amount
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, Quote $oQuote, $dAmount)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'calculation');

        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('api_version', '3.10');

        $this->addParameter('clearingtype', $oPayment->getClearingtype());
        $this->addParameter('financingtype', $oPayment->getSubType());

        $this->addParameter('amount', number_format($dAmount, 2, '.', '') * 100);
        $this->addParameter('currency', $oQuote->getQuoteCurrencyCode());

        $oBilling = $oQuote->getBillingAddress();
        $this->addParameter('country', $oBilling->getCountryId());
        $this->addParameter('lastname', $oBilling->getLastname());

        return $this->send($oPayment);
    }
}
