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

namespace Payone\Core\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Source class for existing SEPA countries
 */
class SepaCountry implements ArrayInterface
{
    /**
     * All SEPA countries
     *
     * @var array
     */
    protected $aSepaCountries = [
        "AX", "AT", "BE", "BG", "HR", "CY", "CZ", "DK", "EE", "FI", "FR", "GF", "DE", "GI",
        "GR", "GP", "HU", "IS", "IE", "IT", "LV", "LI", "LT", "LU", "MT", "MQ", "YT", "MC",
        "NL", "NO", "PL", "PT", "RE", "RO", "BL", "MF", "PM", "SK", "SI", "ES", "SE", "CH", "GB"
    ];

    /**
     * PAYONE country helper
     *
     * @var \Payone\Core\Helper\Country
     */
    protected $countryHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Country $countryHelper
     */
    public function __construct(\Payone\Core\Helper\Country $countryHelper)
    {
        $this->countryHelper = $countryHelper;
    }

    /**
     * Return existing SEPA countries sorted alphabetically
     *
     * @return array
     */
    public function toOptionArray()
    {
        $aOptions = [];
        foreach ($this->aSepaCountries as $sCountryCode) {
            $sName = $this->countryHelper->getCountryNameByIso2($sCountryCode);
            if ($sName) {
                $aOptions[$sName] = [
                    'value' => $sCountryCode,
                    'label' => $sName,
                ];
            }
        }
        ksort($aOptions);
        return $aOptions;
    }
}
