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

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\Database;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class DatabaseTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $databaseResource;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getId')->willReturn(15);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->connection = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchOne', 'fetchAll', 'select', 'from', 'where', 'limit', 'order', 'update', 'joinInner'])
            ->getMock();
        $this->connection->method('select')->willReturn($this->connection);
        $this->connection->method('from')->willReturn($this->connection);
        $this->connection->method('where')->willReturn($this->connection);
        $this->connection->method('limit')->willReturn($this->connection);
        $this->connection->method('order')->willReturn($this->connection);
        $this->connection->method('joinInner')->willReturn($this->connection);

        $this->databaseResource = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $this->databaseResource->method('getConnection')->willReturn($this->connection);

        $this->database = $this->objectManager->getObject(Database::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'databaseResource' => $this->databaseResource
        ]);
    }

    public function testGetStateByStatus()
    {
        $expected = 'complete';

        $this->databaseResource->method('getTableName')->willReturn('sales_order_status_state');
        $this->connection->method('fetchOne')->willReturn($expected);

        $result = $this->database->getStateByStatus($expected);
        $this->assertEquals($expected, $result);
    }

    public function testGetOrderIncrementIdByTxid()
    {
        $expected = '000000123';

        $this->databaseResource->method('getTableName')->willReturn('payone_protocol_api');
        $this->connection->method('fetchOne')->willReturn($expected);

        $result = $this->database->getOrderIncrementIdByTxid('200001234');
        $this->assertEquals($expected, $result);
    }

    public function testGetModuleInfo()
    {
        $expected = [
            ['module' => 'Test_Module', 'schema_version' => '1.2.3'],
            ['module' => 'Another_Module', 'schema_version' => '2.1.7']
        ];

        $this->databaseResource->method('getTableName')->willReturn('setup_module');
        $this->connection->method('fetchAll')->willReturn($expected);

        $result = $this->database->getModuleInfo();
        $this->assertEquals($expected, $result);
    }

    public function testGetIncrementIdByOrderId()
    {
        $expected = '000000001';

        $this->databaseResource->method('getTableName')->willReturn('sales_order');
        $this->connection->method('fetchOne')->willReturn($expected);

        $result = $this->database->getIncrementIdByOrderId('1');
        $this->assertEquals($expected, $result);
    }

    public function testGetPayoneUserIdByCustNr()
    {
        $expected = '15';

        $this->databaseResource->method('getTableName')->willReturn('payone_protocol_transactionstatus');
        $this->connection->method('fetchOne')->willReturn($expected);

        $result = $this->database->getPayoneUserIdByCustNr('803');
        $this->assertEquals($expected, $result);
    }

    public function testGetSequenceNumber()
    {
        $expected = 38;

        $this->databaseResource->method('getTableName')->willReturn('payone_protocol_transactionstatus');
        $this->connection->method('fetchOne')->willReturn($expected - 1);

        $result = $this->database->getSequenceNumber('1207');
        $this->assertEquals($expected, $result);
    }

    public function testGetSequenceNumberNull()
    {
        $expected = 0;

        $this->databaseResource->method('getTableName')->willReturn('payone_protocol_transactionstatus');
        $this->connection->method('fetchOne')->willReturn(null);

        $result = $this->database->getSequenceNumber('1207');
        $this->assertEquals($expected, $result);
    }

    public function testGetConfigParamWithoutCache()
    {
        $expected = '17123';

        $this->databaseResource->method('getTableName')->willReturn('payone_protocol_transactionstatus');
        $this->connection->method('fetchOne')->willReturn($expected);

        $result = $this->database->getConfigParamWithoutCache('mid');
        $this->assertEquals($expected, $result);
    }

    public function testGetOldAddressStatus()
    {
        $expected = 'G';

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFirstname', 'getLastname', 'getStreet', 'getCity', 'getRegion', 'getPostcode', 'getCountryId', 'getId', 'getCustomerId', 'getAddressType'])
            ->getMock();
        $address->method('getFirstname')->willReturn('Paul');
        $address->method('getLastname')->willReturn('Payer');
        $address->method('getStreet')->willReturn(['Teststr. 3']);
        $address->method('getCity')->willReturn('Paycity');
        $address->method('getRegion')->willReturn('Bremen');
        $address->method('getPostcode')->willReturn('12345');
        $address->method('getCountryId')->willReturn('DE');
        $address->method('getId')->willReturn('5');
        $address->method('getCustomerId')->willReturn('18');
        $address->method('getAddressType')->willReturn('billing');

        $this->databaseResource->method('getTableName')->willReturn('quote_address');
        $this->connection->method('fetchOne')->willReturn($expected);

        $result = $this->database->getOldAddressStatus($address);
        $this->assertEquals($expected, $result);

        $result = $this->database->getOldAddressStatus($address, false);
        $this->assertEquals($expected, $result);
    }

    public function testRelabelTransaction()
    {
        $expected = '1';

        $this->databaseResource->method('getTableName')->willReturn('sales_payment_transaction');
        $this->connection->method('update')->willReturn($expected);

        $result = $this->database->relabelTransaction('1', '2', '3');
        $this->assertEquals($expected, $result);
    }

    public function testRelabelApiProtocol()
    {
        $expected = '1';

        $this->databaseResource->method('getTableName')->willReturn('payone_protocol_api');
        $this->connection->method('update')->willReturn($expected);

        $result = $this->database->relabelApiProtocol('1', '2');
        $this->assertEquals($expected, $result);
    }

    public function testRelabelOrderPayment()
    {
        $expected = '1';

        $this->databaseResource->method('getTableName')->willReturn('sales_order_payment');
        $this->connection->method('update')->willReturn($expected);

        $result = $this->database->relabelOrderPayment('1', '2');
        $this->assertEquals($expected, $result);
    }

    public function testGetNotHandledTransactionsByOrderId()
    {
        $expected = [
            ['id' => '5'],
        ];

        $this->databaseResource->method('getTableName')->willReturn('payone_protocol_transactionstatus');
        $this->connection->method('fetchAll')->willReturn($expected);

        $result = $this->database->getNotHandledTransactionsByOrderId(5);
        $this->assertEquals($expected, $result);
    }
}
