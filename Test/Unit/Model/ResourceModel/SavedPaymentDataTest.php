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

use Payone\Core\Helper\Payment;
use Payone\Core\Model\ResourceModel\SavedPaymentData as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ResourceConnection;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\DB\Select;

class SavedPaymentDataTest extends BaseTestCase
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

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->connection = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'order', 'from'])
            ->addMethods(['fetchAll', 'fetchOne', 'select', 'insert', 'update', 'delete'])
            ->getMock();
        $this->connection->method('select')->willReturn($this->connection);
        $this->connection->method('from')->willReturn($this->connection);
        $this->connection->method('where')->willReturn($this->connection);
        $this->connection->method('order')->willReturn($this->connection);
        $this->connection->method('update')->willReturn($this->connection);
        $this->connection->method('delete')->willReturn($this->connection);

        $resource = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $resource->method('getConnection')->willReturn($this->connection);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResources')->willReturn($resource);

        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('getConfigParam')->willReturn('12345');
        $paymentHelper->method('getAvailableCreditcardTypes')->willReturn([
            ['id' => 'V'],
            ['id' => 'M'],
        ]);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'paymentHelper' => $paymentHelper
        ]);
    }

    public function testAddSavedPaymentData()
    {
        $this->connection->method('fetchOne')->willReturn(null);
        $this->connection->method('fetchAll')->willReturn([]);

        $data = ['a' => 'b', '1' => '2'];
        $result = $this->classToTest->addSavedPaymentData(5, 'payone_creditcard', $data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testAddSavedPaymentDataHasData()
    {
        $data = ['a' => 'b', '1' => '2', 'cardtype' => 'V'];
        $fetchReturn = [
            [
                'id' => 5,
                'payment_data' => $this->classToTest->encryptPaymentData($data)
            ]
        ];
        $this->connection->method('fetchOne')->willReturn(5);
        $this->connection->method('fetchAll')->willReturn($fetchReturn);

        $data = ['a' => 'b', '1' => '2'];
        $result = $this->classToTest->addSavedPaymentData(5, 'payone_creditcard', $data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testDeletePaymentData()
    {
        $data = ['a' => 'b', '1' => '2', 'firstname' => 'A', 'lastname' => 'B', 'cardtype' => 'V'];
        $fetchReturn = [
            [
                'id' => 5,
                'payment_data' => $this->classToTest->encryptPaymentData($data)
            ]
        ];
        $this->connection->method('fetchAll')->willReturn($fetchReturn);

        $result = $this->classToTest->deletePaymentData(5, 10, 'payone_creditcard');
        $this->assertNull($result);
    }

    public function testGetSavedPaymentData()
    {
        $fetchReturn = [
            [
                'id' => 5,
                'payment_data' => 'a:5x12aaaTest'
            ]
        ];
        $this->connection->method('fetchAll')->willReturn($fetchReturn);

        $result = $this->classToTest->getSavedPaymentData(5);
        $this->assertEmpty($result);
    }

    public function testGetSavedPaymentDataNoCustomerId()
    {
        $result = $this->classToTest->getSavedPaymentData(false);
        $this->assertEmpty($result);
    }

    public function testSetDefault()
    {
        $result = $this->classToTest->setDefault(5, 10);
        $this->assertNull($result);
    }
}
