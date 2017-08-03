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

namespace Payone\Core\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Payone\Core\Model\PayoneConfig;

/**
 * Source class for existing Amazon button languages
 */
class AmazonButtonLanguage implements ArrayInterface
{
    /**
     * Return existing Amazon button languages
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => __('Automatic'),
            ],
            [
                'value' => 'en-GB',
                'label' => __('Englisch')
            ],
            [
                'value' => 'de-DE',
                'label' => __('Deutsch')
            ],
            [
                'value' => 'fr-FR',
                'label' => __('French')
            ],
            [
                'value' => 'it-IT',
                'label' => __('Italian')
            ],
            [
                'value' => 'es-ES',
                'label' => __('Spanish')
            ]
        ];
    }
}
