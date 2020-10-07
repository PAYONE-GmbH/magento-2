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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Source class for existing BusinessRelation types
 */
class BusinessRelation implements ArrayInterface
{
    const B2B = 'b2b';
    const B2C = 'b2c';

    /**
     * Return existing business relation types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::B2B,
                'label' => __('B2B'),
            ],
            [
                'value' => self::B2C,
                'label' => __('B2C')
            ],
        ];
    }

    /**
     * Return array with all available options
     *
     * @return array
     */
    public static function getAvailableOptions()
    {
        return [
            self::B2B,
            self::B2C,
        ];
    }
}
