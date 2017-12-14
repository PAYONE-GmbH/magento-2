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

use Payone\Core\Model\ResourceModel\ApiLog as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Api\Request\Base;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ApiLogTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getStoreId')->willReturn('15');

        $connection = $this->getMockBuilder(AdapterInterface::class)->disableOriginalConstructor()->getMock();

        $resource = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $resource->method('getConnection')->willReturn($connection);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResources')->willReturn($resource);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'shopHelper' => $shopHelper
        ]);
    }

    public function testHandlePayPalReturn()
    {
        $params = [
            'reference' => 'pre_12345',
            'request' => 'authorization',
            'mid' => '12345',
            'aid' => '123456',
        ];

        $request = $this->getMockBuilder(Base::class)->disableOriginalConstructor()->getMock();
        $request->method('getParameters')->willReturn($params);
        $request->method('getOrderId')->willReturn('12345');

        $response = ['txid' => '12345'];
        $status = 'appointed';

        $result = $this->classToTest->addApiLogEntry($request, $response, $status);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }
}
