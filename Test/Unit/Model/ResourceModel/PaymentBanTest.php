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

use Payone\Core\Model\ResourceModel\PaymentBan as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ResourceConnection;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\DB\Select;

class PaymentBanTest extends BaseTestCase
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
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->connection = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchAll', 'select', 'from', 'where', 'insert', 'order'])
            ->getMock();
        $this->connection->method('select')->willReturn($this->connection);
        $this->connection->method('from')->willReturn($this->connection);
        $this->connection->method('where')->willReturn($this->connection);
        $this->connection->method('order')->willReturn($this->connection);

        $resource = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $resource->method('getConnection')->willReturn($this->connection);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResources')->willReturn($resource);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context
        ]);
    }

    public function testGetBanEndDate()
    {
        $time = time();
        $result = $this->classToTest->getBanEndDate(24);
        $this->assertGreaterThan($time, strtotime($result));
    }

    public function testAddPaymentBan()
    {
        $result = $this->classToTest->addPaymentBan('payone_debit', 5, 24);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testGetPaymentBans()
    {
        $fetchReturn = [['payment_method' => 'payone_debit', 'to_date' => '2020-12-12 12:20:00']];
        $this->connection->method('fetchAll')->willReturn($fetchReturn);
        $expected = ['payone_debit' => '2020-12-12 12:20:00'];
        $result = $this->classToTest->getPaymentBans(5);
        $this->assertEquals($expected, $result);
    }
}
