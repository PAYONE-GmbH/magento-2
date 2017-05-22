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

use Locale;
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Model\Source\AddressCheckType;
use Payone\Core\Model\Source\CreditratingCheckType;

/**
 * Class for the PAYONE Server API request "consumerscore"
 */
class Consumerscore extends AddressRequest
{

    /**
     * Object of CheckedAddresses resource
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
     * Check enabled status
     *
     * @return bool
     */
    protected function isCheckEnabled()
    {
        if (!$this->shopHelper->getConfigParam('enabled', 'creditrating', 'payone_protect')) {
            return false;
        }
        return true;
    }

    /**
     * Get check type for combined address checks but enforce 'Boniversum Person'
     * if the configured credit rating check type is 'Boniversum VERITA'
     *
     * @return string
     */
    protected function getCombinedAdressCheckType()
    {
        $creditRatingCheckType = $this->shopHelper->getConfigParam('type', 'creditrating', 'payone_protect');
        if ($creditRatingCheckType == CreditratingCheckType::BONIVERSUM_VERITA) {
            return AddressCheckType::BONIVERSUM_PERSON;
        }
        return $this->shopHelper->getConfigParam('addresscheck', 'creditrating', 'payone_protect');
    }

    /**
     * Send request "addresscheck" to PAYONE server API
     *
     * @param  AddressInterface $oAddress
     * @return array|bool
     */
    public function sendRequest(AddressInterface $oAddress)
    {
        if (!$this->isCheckEnabled() || $oAddress->getCountryId() != 'DE') {
            return true;
        }

        $this->addParameter('request', 'consumerscore');
        $this->addParameter('mode', $this->shopHelper->getConfigParam('mode', 'creditrating', 'payone_protect')); //Operationmode live or test
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); //ID of PayOne Sub-Account
        $this->addParameter('addresschecktype', $this->getCombinedAdressCheckType());
        $this->addParameter('consumerscoretype', $this->shopHelper->getConfigParam('type', 'creditrating', 'payone_protect'));
        $this->addParameter('language', Locale::getPrimaryLanguage(Locale::getDefault()));

        $this->addAddress($oAddress);
        if ($this->addressesChecked->wasAddressCheckedBefore($oAddress, true) === false) {
            $aResponse = $this->send();
            if ($aResponse['score'] === 'U') {
                $unknownDefault = $this->shopHelper->getConfigParam('unknown_value', 'creditrating', 'payone_protect');
                $aResponse['score'] = empty($unknownDefault) ? 'G' : $unknownDefault;
            }
            if ($aResponse['status'] == 'VALID') {
                $this->addressesChecked->addCheckedAddress($oAddress, $aResponse, true);
            }

            return $aResponse;
        }
        return true;
    }
}
