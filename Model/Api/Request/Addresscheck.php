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

/**
 * Class for the PAYONE Server API request "addresscheck"
 */
class Addresscheck extends AddressRequest
{
    /*
     * Array of valid countries for addresscheck basic
     *
     * @var array
     */
    protected $aValidCountrys = [
        'BE',
        'DK',
        'DE',
        'FI',
        'FR',
        'IT',
        'CA',
        'LU',
        'NL',
        'NO',
        'AT',
        'PL',
        'PT',
        'SE',
        'CH',
        'SK',
        'ES',
        'CZ',
        'HU',
        'US',
    ];
    
    /**
     * Checked addresses resource model
     * 
     * @var \Payone\Core\Model\ResourceModel\CheckedAddresses 
     */
    protected $addressesChecked;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                          $shopHelper
     * @param \Payone\Core\Helper\Environment                   $environmentHelper
     * @param \Payone\Core\Helper\Api                           $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog           $apiLog
     * @param \Payone\Core\Helper\Customer                      $customerHelper
     * @param \Payone\Core\Model\ResourceModel\CheckedAddresses $addressesChecked
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Payone\Core\Model\ResourceModel\CheckedAddresses $addressesChecked
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog, $customerHelper);
        $this->addressesChecked = $addressesChecked;
    }

    /**
     * Get addresscheck type
     *
     * @param  bool $blIsBillingAddress
     * @return string
     */
    protected function getAddresscheckType($blIsBillingAddress)
    {
        $sConfigField = 'check_billing';
        if ($blIsBillingAddress === false) {
            $sConfigField = 'check_shipping';
        }
        return $this->shopHelper->getConfigParam($sConfigField, 'address_check', 'payone_protect');
    }

    /**
     * Check if the addresscheck is available for the given check-type and address-country
     *
     * @param  string                                   $sAddresscheckType
     * @param  \Magento\Quote\Api\Data\AddressInterface $oAddress
     * @return bool
     */
    protected function validateTypeToCountry($sAddresscheckType, \Magento\Quote\Api\Data\AddressInterface $oAddress)
    {
        if ($sAddresscheckType == 'PE' && $oAddress->getCountryId() != 'DE') {
            //AddressCheck Person only available for germany
            return false;
        }
        if ($sAddresscheckType == 'BA' && array_search($oAddress->getCountryId(), $this->aValidCountrys) === false) {
            //AddressCheck Basic only available for certain countries
            return false;
        }
        return true;
    }

    /**
     * Check enabled status
     *
     * @param  bool $blIsBillingAddress
     * @return bool
     */
    protected function isCheckEnabled($blIsBillingAddress)
    {
        if (!$this->shopHelper->getConfigParam('enabled', 'address_check', 'payone_protect')) {
            return false; // address check was disabled
        }
        if ($blIsBillingAddress && $this->shopHelper->getConfigParam('check_billing', 'address_check', 'payone_protect') == 'NO') {
            return false; // address check was disabled for the billing address
        }
        if (!$blIsBillingAddress && $this->shopHelper->getConfigParam('check_shipping', 'address_check', 'payone_protect') == 'NO') {
            return false; // address check was disabled for the shipping address
        }
        return true;
    }

    /**
     * Send request "addresscheck" to PAYONE server API
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $oAddress
     * @param  bool                                     $blIsBillingAddress
     * @return array|bool
     */
    public function sendRequest(\Magento\Quote\Api\Data\AddressInterface $oAddress, $blIsBillingAddress = false)
    {
        if (!$this->isCheckEnabled($blIsBillingAddress)) { // check not needed because of configuration
            return true;
        }

        $sType = $this->getAddresscheckType($blIsBillingAddress);
        if (!$this->validateTypeToCountry($sType, $oAddress)) {
            return ['wrongCountry' => true]; //Simulate successful check
        }

        $this->addParameter('request', 'addresscheck');
        $this->addParameter('mode', $this->shopHelper->getConfigParam('mode', 'address_check', 'payone_protect')); //Operationmode live or test
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); //ID of PayOne Sub-Account
        $this->addParameter('addresschecktype', $sType);
        $this->addParameter('language', $this->shopHelper->getLocale());
        $this->addAddress($oAddress);

        if ($this->addressesChecked->wasAddressCheckedBefore($oAddress) === false) {
            $aResponse = $this->send();
            if ($aResponse['status'] == 'VALID') {
                $this->addressesChecked->addCheckedAddress($oAddress, $aResponse);
            }
            return $aResponse;
        }
        return true;
    }
}
