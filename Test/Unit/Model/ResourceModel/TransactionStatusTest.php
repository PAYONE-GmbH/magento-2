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

use Payone\Core\Helper\Toolkit;
use Payone\Core\Model\ResourceModel\TransactionStatus as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DB\Select;

class TransactionStatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
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
        $this->objectManager = new ObjectManager($this);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getId')->willReturn('15');

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->connection = $this->getMockBuilder(AdapterInterface::class)->disableOriginalConstructor()->getMock();

        $resource = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $resource->method('getConnection')->willReturn($this->connection);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResources')->willReturn($resource);

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('isUTF8')->willReturn(false);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'toolkitHelper' => $toolkitHelper
        ]);
    }

    public function testAddTransactionLogEntry()
    {
        $post = 15;

        $request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPostValue', 'getParam'])
            ->getMock();
        $request->method('getPostValue')->willReturn($post);
        $request->method('getParam')->willReturn(15);

        $context = $this->getMockBuilder(ActionContext::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($request);

        $result = $this->classToTest->addTransactionLogEntry($context);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testGetParam()
    {
        $expected = 'default';
        $result = $this->classToTest->getParam('key', $expected);
        $this->assertEquals($expected, $result);
    }

    public function testGetAppointedIdByTxid()
    {
        $expected = '54321';

        $select = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $select->method('from')->willReturn($select);
        $select->method('where')->willReturn($select);

        $this->connection->method('select')->willReturn($select);
        $this->connection->method('fetchOne')->willReturn($expected);

        $result = $this->classToTest->getAppointedIdByTxid('12345');
        $this->assertEquals($expected, $result);
    }
}
