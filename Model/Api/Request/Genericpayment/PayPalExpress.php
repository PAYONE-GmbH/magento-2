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
use Payone\Core\Model\Methods\Paypal;

/**
 * Class for the PAYONE Server API request genericpayment - "setexpresscheckout" and "getexpresscheckoutdetails"
 */
class PayPalExpress extends Base
{
    /**
     * Send request to PAYONE Server-API with
     * request-type "genericpayment"
     *
     * @param Quote $oQuote
     * @param Paypal $oPayment
     * @param string $sWorkorderId
     *
     * @return array Response
     */
    public function sendRequest(Quote $oQuote, Paypal $oPayment, $sWorkorderId = false)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('clearingtype', 'wlt');
        $this->addParameter('wallettype', 'PPE');
        $this->addParameter('amount', number_format($oQuote->getGrandTotal(), 2, '.', '') * 100);
        $this->addParameter('currency', $oQuote->getQuoteCurrencyCode());
        $this->addParameter('narrative_text', 'Test');

        if ($sWorkorderId !== false) {
            $this->addParameter('workorderid', $sWorkorderId);
            $this->addParameter('add_paydata[action]', 'getexpresscheckoutdetails');
        } else {
            $this->addParameter('add_paydata[action]', 'setexpresscheckout');
        }

        $this->addRedirectUrls($oPayment);

        return $this->send($oPayment);
    }
}
