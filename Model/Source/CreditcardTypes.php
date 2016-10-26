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
 * Source class for existing creditcard types
 */
class CreditcardTypes implements ArrayInterface
{
    /**
     * Creditcard types
     *
     * @var array
     */
    protected static $aTypes = [
        'V' => 'Visa',
        'M' => 'Mastercard',
        'A' => 'American Express',
        'D' => 'Diners Club',
        'J' => 'JCB',
        'O' => 'Maestro International',
        'C' => 'Discover',
        'B' => 'Carte Bleue',
    ];

    /**
     * Return available creditcard type array
     *
     * @return array
     */
    public static function getCreditcardTypes()
    {
        return self::$aTypes;
    }

    /**
     * Return existing creditcard types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $aOptions = [];
        foreach (self::$aTypes as $sId => $sTitle) {
            $aOptions[] = [
                'value' => $sId,
                'label' => $sTitle,
            ];
        }
        return $aOptions;
    }
}
