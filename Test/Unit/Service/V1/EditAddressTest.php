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

namespace Payone\Core\Test\Unit\Service\V1\Data;

use Payone\Core\Service\V1\EditAddress as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Payone\Core\Service\V1\Data\EditAddressResponse;
use Payone\Core\Service\V1\Data\EditAddressResponseFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Customer\Model\Address;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;

class EditAddressTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var EditAddressResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(EditAddressResponse::class);
        $responseFactory = $this->getMockBuilder(EditAddressResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $address = $this->getMockBuilder(CustomerAddressInterface::class)->disableOriginalConstructor()->getMock();

        $addressRepository = $this->getMockBuilder(AddressRepository::class)->disableOriginalConstructor()->getMock();
        $addressRepository->method('getById')->willReturn($address);

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'addressRepository' => $addressRepository
        ]);
    }

    public function testEditAddress()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->editAddress($addressData);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }
}
