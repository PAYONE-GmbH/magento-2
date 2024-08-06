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

namespace Payone\Core\Model\Api\Request\Genericpayment;

use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Quote\Model\Quote;

/**
 * Class for the PAYONE Server API request genericpayment - "createCheckoutSessionPayload"
 */
class CreateCheckoutSessionPayload extends Base
{
    /**
     * Send request to PAYONE Server-API with request-type "genericpayment" and action "createCheckoutSessionPayload"
     *
     * @param  PayoneMethod $oPayment payment object
     * @param  Quote        $oQuote
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, Quote $oQuote)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'createCheckoutSessionPayload');

        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account

        $this->addParameter('clearingtype', 'wlt');
        $this->addParameter('wallettype', 'AMP');

        $this->addParameter('amount', number_format($this->apiHelper->getQuoteAmount($oQuote), 2, '.', '') * 100); // add price to request
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromQuote($oQuote));

        $oPayment->setNeedsReturnedUrl(true);
        $this->addRedirectUrls($oPayment);
        $oPayment->setNeedsReturnedUrl(false);

        // Optional and not yet implemented
        #$this->addParameter('add_paydata[specialRestrictions]', "");
        #$this->addParameter('add_paydata[addressRestrictions_type]', "");
        #$this->addParameter('add_paydata[addressRestrictions_country_[n]]', "");
        #$this->addParameter('add_paydata[addressRestrictions_stateOrRegions_[n]]', "");
        #$this->addParameter('add_paydata[addressRestrictions_zipCodes_[n]]', "");

        return $this->send($oPayment);
    }
}
