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

namespace Payone\Core\Test\Unit\Model\UiComponent;

use Payone\Core\Model\UiComponent\DataProvider as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Filter;
use Payone\Core\Helper\Database;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class DataProviderTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var FilterBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterBuilder;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();

        $filter = $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock();
        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()->getMock();
        $this->filterBuilder->method('setField')->willReturn($this->filterBuilder);
        $this->filterBuilder->method('setValue')->willReturn($this->filterBuilder);
        $this->filterBuilder->method('setConditionType')->willReturn($this->filterBuilder);
        $this->filterBuilder->method('create')->willReturn($filter);
    }

    public function testGetRawStatusArrayDirectReturn()
    {
        $data = [
            'config' => [
                'direct' => 'return'
            ]
        ];

        $classToTest = $this->objectManager->getObject(ClassToTest::class, ['data' => $data]);

        $result = $classToTest->getConfigData();
        $this->assertNotEmpty($result);
    }

    public function testGetRawStatusArrayIncrementId()
    {
        $data = [
            'config' => [
                'update_url' => 'http://testdomain.com',
                'filter_url_params' => [
                    'param' => '*'
                ]
            ]
        ];

        $this->request->method('getParam')->willReturn('000000000001');
        $classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'data' => $data,
            'request' => $this->request,
            'filterBuilder' => $this->filterBuilder
        ]);

        $result = $classToTest->getConfigData();
        $this->assertNotEmpty($result);
    }

    public function testGetRawStatusArray()
    {
        $data = [
            'config' => [
                'update_url' => 'http://testdomain.com',
                'filter_url_params' => [
                    'param' => '*'
                ]
            ]
        ];

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getIncrementIdByOrderId')->willReturn('0000000001');
        $this->request->method('getParam')->willReturn('517');
        $classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'data' => $data,
            'request' => $this->request,
            'filterBuilder' => $this->filterBuilder,
            'databaseHelper' => $databaseHelper
        ]);

        $result = $classToTest->getConfigData();
        $this->assertNotEmpty($result);
    }
}
