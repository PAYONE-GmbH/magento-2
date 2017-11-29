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

namespace Payone\Core\Test\Unit\Model\ResourceModel;

use Payone\Core\Model\ResourceModel\CheckedAddresses as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;
use Magento\Framework\DB\Select;

class CheckedAddressesTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $address;

    /**
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    /**
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $this->address->method('getFirstname')->willReturn('Paul');
        $this->address->method('getLastname')->willReturn('Payer');
        $this->address->method('getCompany')->willReturn('Testcompany Ldt.');
        $this->address->method('getStreet')->willReturn(['Teststr. 12', '3rd floor']);
        $this->address->method('getPostcode')->willReturn('54321');
        $this->address->method('getCity')->willReturn('Berlin');
        $this->address->method('getCountryId')->willReturn('DE');
        $this->address->method('getRegionCode')->willReturn('');

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->connection = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchOne', 'select', 'from', 'where', 'insert'])
            ->getMock();
        $this->connection->method('select')->willReturn($this->connection);
        $this->connection->method('from')->willReturn($this->connection);
        $this->connection->method('where')->willReturn($this->connection);

        $resource = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $resource->method('getConnection')->willReturn($this->connection);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResources')->willReturn($resource);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'shopHelper' => $this->shopHelper
        ]);
    }

    public function testAddCheckedAddress()
    {
        $response = [
            'firstname' => 'Paul',
            'lastname' => 'Payer',
            'company' => 'Testcompany Ltd.',
            'street' => 'Teststr. 12',
            'zip' => '12345',
            'city' => 'Berlin',
            'country' => 'DE',
            'state' => '',
        ];

        $result = $this->classToTest->addCheckedAddress($this->address, $response);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testWasAddressCheckedBeforeNoLifetime()
    {
        $this->shopHelper->method('getConfigParam')->willReturn(null);

        $result = $this->classToTest->wasAddressCheckedBefore($this->address, true);
        $this->assertFalse($result);
    }

    public function testWasAddressCheckedBeforeNoResult()
    {
        $this->shopHelper->method('getConfigParam')->willReturn(5);
        $this->connection->method('fetchOne')->willReturn(false);

        $result = $this->classToTest->wasAddressCheckedBefore($this->address, true);
        $this->assertFalse($result);
    }

    public function testWasAddressCheckedBefore()
    {
        $this->shopHelper->method('getConfigParam')->willReturn(5);
        $this->connection->method('fetchOne')->willReturn('2017-01-01 01:01:01');

        $result = $this->classToTest->wasAddressCheckedBefore($this->address, true);
        $this->assertTrue($result);
    }
}
