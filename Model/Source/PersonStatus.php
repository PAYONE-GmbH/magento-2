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
 * Source class for person status states
 */
class PersonStatus implements ArrayInterface
{
    const NONE = 'NONE'; //NONE: no verification of personal data carried out
    const PAB = 'PAB'; //PAB: first name & surname unknown
    const PHB = 'PHB'; //PHB: surname known
    const PKI = 'PKI'; //PKI: ambiguity in name and address
    const PNP = 'PNP'; //PNP: address cannot be checked, e.g. fake name used
    const PNZ = 'PNZ'; //PNZ: cannot be delivered (any longer)
    const PPB = 'PPB'; //PPB: first name & surname unknown
    const PPF = 'PPF'; //PPF: postal address details incorrect
    const PPV = 'PPV'; //PPV: person deceased
    const PUG = 'PUG'; //PUG: postal address details correct but building unknown
    const PUZ = 'PUZ'; //PUZ: person has moved, address not corrected
    const UKN = 'UKN'; //UKN: unknown return values are mapped to UKN

    /**
     * Return existing person status states
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::NONE,
                'label' => __(self::NONE),
            ],
            [
                'value' => self::PPB,
                'label' => __(self::PPB),
            ],
            [
                'value' => self::PHB,
                'label' => __(self::PHB),
            ],
            [
                'value' => self::PAB,
                'label' => __(self::PAB),
            ],
            [
                'value' => self::PKI,
                'label' => __(self::PKI),
            ],
            [
                'value' => self::PNZ,
                'label' => __(self::PNZ),
            ],
            [
                'value' => self::PPV,
                'label' => __(self::PPV),
            ],
            [
                'value' => self::PPF,
                'label' => __(self::PPF),
            ],
            [
                'value' => self::PUG,
                'label' => __(self::PUG),
            ],
            [
                'value' => self::PUZ,
                'label' => __(self::PUZ),
            ],
            [
                'value' => self::UKN,
                'label' => __(self::UKN),
            ],
        ];
    }
}
