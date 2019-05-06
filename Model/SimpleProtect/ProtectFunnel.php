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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\SimpleProtect;

use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Model\Source\AddressCheckType;
use Payone\Core\Model\Source\CreditratingCheckType;
use Magento\Framework\Exception\InputException;
use Payone\Core\Model\Api\Response\AddresscheckResponse;
use Payone\Core\Model\Api\Response\ConsumerscoreResponse;

/**
 * Model to funnel all existing protect requests
 */
class ProtectFunnel
{
    /**
     * Array of valid countries for addresscheck basic
     *
     * @var array
     */
    protected $aValidCountrys = [
        'AT', 'BE', 'CA', 'CZ', 'CH', 'DE', 'DK', 'ES', 'FI', 'FR', 'HU', 'IT', 'LU', 'NL', 'NO', 'PL', 'PT', 'SE', 'SK', 'US'
    ];

    /**
     * PAYONE addresscheck request model
     *
     * @var \Payone\Core\Model\Api\Request\Addresscheck
     */
    protected $addresscheck;

    /**
     * PAYONE consumerscore request model
     *
     * @var \Payone\Core\Model\Api\Request\Consumerscore
     */
    protected $consumerscore;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\Api\Request\Addresscheck             $addresscheck
     * @param \Payone\Core\Model\Api\Request\Consumerscore            $consumerscore
     */
    public function __construct(
        \Payone\Core\Model\Api\Request\Addresscheck $addresscheck,
        \Payone\Core\Model\Api\Request\Consumerscore $consumerscore
    ) {
        $this->addresscheck = $addresscheck;
        $this->consumerscore = $consumerscore;
    }

    /**
     * Validating given addresscheck type
     * Throws InputException if invalid type was given
     *
     * @param  string $sType
     * @return void
     * @throws InputException
     */
    protected function validateAddresscheckType($sType)
    {
        if (!in_array($sType, AddressCheckType::getAvailableOptions())) {
            throw InputException::invalidFieldValue('sType', $sType);
        }
    }

    /**
     * Validating given consumerscore type
     * Throws InputException if invalid type was given
     *
     * @param  string $sType
     * @return void
     * @throws InputException
     */
    protected function validateConsumerscoreType($sType)
    {
        if (!in_array($sType, CreditratingCheckType::getAvailableOptions())) {
            throw InputException::invalidFieldValue('sType', $sType);
        }
    }

    /**
     * Validates input parameters and executes an addresscheck
     *
     * @param  AddressInterface $oAddress
     * @param  string           $sAddresscheckType
     * @param  string           $sMode
     * @return AddresscheckResponse|bool
     */
    public function executeAddresscheck(AddressInterface $oAddress, $sMode, $sAddresscheckType)
    {
        if ($sAddresscheckType == AddressCheckType::PERSON && $oAddress->getCountryId() != 'DE') {
            return false; //AddressCheck Person only available for german addresses
        }
        if ($sAddresscheckType == AddressCheckType::BASIC && !in_array($oAddress->getCountryId(), $this->aValidCountrys)) {
            return false; //AddressCheck Basic only available for certain countries
        }

        $this->validateAddresscheckType($sAddresscheckType);

        $aResponse =  $this->addresscheck->sendRequest($oAddress, $sMode, $sAddresscheckType);

        return new AddresscheckResponse($aResponse);
    }

    /**
     * Validates input parameters and executes a consumerscore
     *
     * @param  AddressInterface $oAddress
     * @param  string           $sMode
     * @param  string           $sConsumerscoreType
     * @param  string           $sAddresscheckType
     * @return ConsumerscoreResponse|bool
     */
    public function executeConsumerscore(AddressInterface $oAddress, $sMode, $sConsumerscoreType, $sAddresscheckType = AddressCheckType::NONE)
    {
        if ($oAddress->getCountryId() != 'DE') {
            return true; // Consumerscore is only available for german addresses
        }

        if ($sConsumerscoreType == CreditratingCheckType::BONIVERSUM_VERITA) {
            $sAddresscheckType = AddressCheckType::BONIVERSUM_PERSON; // 'Boniversum Person' needs to be enforced in this case
        }

        $this->validateConsumerscoreType($sConsumerscoreType);
        $this->validateAddresscheckType($sAddresscheckType);

        $aResponse = $this->consumerscore->sendRequest($oAddress, $sMode, $sConsumerscoreType, $sAddresscheckType);

        return new ConsumerscoreResponse($aResponse);
    }
}
