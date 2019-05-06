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

namespace Payone\Core\Service\V1;

use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Api\AddresscheckInterface;
use Payone\Core\Service\V1\Data\AddresscheckResponse;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Web API model for the PAYONE addresscheck
 */
class Addresscheck implements AddresscheckInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\AddresscheckResponseFactory
     */
    protected $responseFactory;

    /**
     * PAYONE Simple Protect implementation
     *
     * @var \Payone\Core\Model\SimpleProtect\SimpleProtectInterface
     */
    protected $simpleProtect;

    /**
     * Constructor
     *
     * @param \Payone\Core\Service\V1\Data\AddresscheckResponseFactory $responseFactory
     * @param \Payone\Core\Model\SimpleProtect\SimpleProtectInterface  $simpleProtect
     */
    public function __construct(
        \Payone\Core\Service\V1\Data\AddresscheckResponseFactory $responseFactory,
        \Payone\Core\Model\SimpleProtect\SimpleProtectInterface $simpleProtect
    ) {
        $this->responseFactory = $responseFactory;
        $this->simpleProtect = $simpleProtect;
    }

    /**
     * Generate the confirm message from the given address
     *
     * @param  AddressInterface $addressData
     * @return string
     */
    protected function getConfirmMessage(AddressInterface $addressData)
    {
        $sMessage  = __('Address corrected. Please confirm.')."\n\n";
        $sMessage .= $addressData->getFirstname().' '.$addressData->getLastname()."\n";

        $mStreet = $addressData->getStreet();
        if (is_array($mStreet)) { // address can be string
            $sMessage .= $mStreet[0]."\n"; // add first line of address array
        } else {
            $sMessage .= $mStreet."\n"; // add string directly
        }
        $sMessage .= $addressData->getPostcode().' '.$addressData->getCity();

        return $sMessage;
    }

    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @param  bool $isBillingAddress
     * @param  bool $isVirtual
     * @param  double $dTotal
     * @return \Payone\Core\Service\V1\Data\AddresscheckResponse
     */
    public function checkAddress(\Magento\Quote\Api\Data\AddressInterface $addressData, $isBillingAddress, $isVirtual, $dTotal)
    {
        /** @var AddresscheckResponse $oResponse */
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false);

        try {
            if ($isBillingAddress === true) {
                $mResponse = $this->simpleProtect->handleEnterOrChangeBillingAddress($addressData, $isBillingAddress, $dTotal);
            } else {
                $mResponse = $this->simpleProtect->handleEnterOrChangeShippingAddress($addressData, $isBillingAddress, $dTotal);
            }

            if (!empty($mResponse)) {
                $oResponse->setData('success', true);
            }
            if ($mResponse instanceof AddressInterface) {
                $oResponse->setData('correctedAddress', $mResponse);
                $oResponse->setData('confirmMessage', $this->getConfirmMessage($mResponse));
            }
        } catch (LocalizedException $exc) {
            $oResponse->setData('errormessage', $exc->getMessage());
        }

        return $oResponse;
    }
}
