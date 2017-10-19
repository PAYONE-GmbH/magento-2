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

namespace Payone\Core\Helper;

use Payone\Core\Model\PayoneConfig;

/**
 * Helper class for everything that has to do with countries
 */
class Country extends \Payone\Core\Helper\Base
{
    /**
     * List of all countries where the state parameter has to be submitted
     *
     * @var array
     */
    static protected $aStateNeeded = [
        'US',
        'CA',
        'CN',
        'JP',
        'MX',
        'BR',
        'AR',
        'ID',
        'TH',
        'IN',
    ];

    /**
     * Country object
     *
     * @var \Magento\Directory\Model\Country
     */
    protected $country;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Directory\Model\Country           $country
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Directory\Model\Country $country
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->country = $country;
    }

    /**
     * Get country name by its ISO2 abbreviation
     *
     * @param  string $sCountryCode
     * @return string|bool
     */
    public function getCountryNameByIso2($sCountryCode)
    {
        $oCountry = $this->country->loadByCode($sCountryCode);
        if ($oCountry) {
            return $oCountry->getName();
        }
        return false;
    }

    /**
     * Get all activated debit SEPA countries
     *
     * @return array
     */
    public function getDebitSepaCountries()
    {
        $aReturn = [];

        $sCountries = $this->getConfigParam('sepa_country', PayoneConfig::METHOD_DEBIT, 'payone_payment');
        if ($sCountries) {
            $aCountries = explode(',', $sCountries);
            foreach ($aCountries as $sCountryCode) {
                $sCountryName = $this->getCountryNameByIso2($sCountryCode);
                if ($sCountryName) {
                    $aReturn[] = [
                        'id' => $sCountryCode,
                        'title' => $sCountryName,
                    ];
                }
            }
        }
        return $aReturn;
    }

    /**
     * Return if state parameter has to be added for the given country
     *
     * @param  string $sIsoToCountry
     * @return bool
     */
    public static function isStateNeeded($sIsoToCountry)
    {
        if (array_search($sIsoToCountry, self::$aStateNeeded) !== false) {
            return true;
        }
        return false;
    }
}
