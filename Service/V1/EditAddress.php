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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Service\V1;

use Payone\Core\Api\EditAddressInterface;

/**
 * Web API model for the PAYONE addresscheck
 */
class EditAddress implements EditAddressInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\EditAddressResponseFactory
     */
    protected $responseFactory;

    /**
     * Address repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * Constructor
     *
     * @param \Payone\Core\Service\V1\Data\EditAddressResponseFactory $responseFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface        $addressRepository
     */
    public function __construct(
        \Payone\Core\Service\V1\Data\EditAddressResponseFactory $responseFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->responseFactory = $responseFactory;
        $this->addressRepository = $addressRepository;
    }

    /**
     * PAYONE editAddress script
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @return \Payone\Core\Service\V1\Data\EditAddressResponse
     */
    public function editAddress(\Magento\Quote\Api\Data\AddressInterface $addressData)
    {
        $address = $this->addressRepository->getById($addressData->getCustomerAddressId());
        $address->setPostcode($addressData->getPostcode());
        $address->setFirstname($addressData->getFirstname());
        $address->setLastname($addressData->getLastname());
        $address->setCity($addressData->getCity());
        $address->setCountryId($addressData->getCountryId());

        $street = $addressData->getStreet();
        if (!is_array($street)) {
            $street = [$street];
        }
        $address->setStreet($street);

        $this->addressRepository->save($address);

        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', true);
        return $oResponse;
    }
}
