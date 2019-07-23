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

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;

/**
 * Model for Paydirekt payment method
 */
class Paydirekt extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_PAYDIREKT;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'wlt';

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

    /**
     * Max length for narrative text parameter
     *
     * @var int
     */
    protected $iNarrativeTextMax = 37;

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aParams = ['wallettype' => 'PDT'];
        if ($this->checkoutSession->getPayoneExpressType() == $this->_code) {
            $aParams['add_paydata[device_fingerprint]'] = $this->checkoutSession->getPayoneDeviceFingerprint();
            $aParams['add_paydata[device_name]'] = $this->checkoutSession->getPayoneUserAgent();
            $aParams['customer_is_present'] = 'no';
            $aParams['recurrence'] = 'oneclick';

            $aParams['add_paydata[terminal_address_city]'] = $this->shopHelper->getConfigParam('city', 'store_information', 'general');
            $aParams['add_paydata[terminal_address_company]'] = $this->shopHelper->getConfigParam('name', 'store_information', 'general');
            $aParams['add_paydata[terminal_address_country]'] = $this->shopHelper->getConfigParam('country_id', 'store_information', 'general'); // CHECKEN!
            $aParams['add_paydata[terminal_address_name]'] = $this->shopHelper->getConfigParam('name', 'store_information', 'general');
            $aParams['add_paydata[terminal_address_streetname]'] = $this->shopHelper->getConfigParam('street_line1', 'store_information', 'general');
            $aParams['add_paydata[terminal_address_streetnumber]'] = '';// IS THIS NECESSARY?
            $aParams['add_paydata[terminal_address_zip]'] = $this->shopHelper->getConfigParam('postcode', 'store_information', 'general');
            $aParams['add_paydata[terminal_id]'] = $this->shopHelper->getConfigParam('name', 'store_information', 'general');

        }
        return $aParams;
    }

    /**
     * Formats the reference number if needed for this payment method
     * The request will fail if there is an underscore in the reference number
     *
     * @param  string $sRefNr
     * @return string
     */
    public function formatReferenceNumber($sRefNr)
    {
        $sRefNr = str_replace('_', '-', $sRefNr);
        return $sRefNr;
    }
}
