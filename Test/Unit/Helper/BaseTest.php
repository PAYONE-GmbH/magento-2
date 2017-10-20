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

use Payone\Core\Helper\Base;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class BaseTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Base
     */
    private $base;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $request = $this->getMockBuilder(Http::class)->disableOriginalConstructor()->getMock();
        $request->method('getParam')->willReturn('value');

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig, 'httpRequest' => $request]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);
        $storeManager->method('getStores')->willReturn(['de' => $store, 'en' => $store, 'fr' => $store, 'nl' => $store]);

        $this->base = $this->objectManager->getObject(Base::class, [
            'context' => $context,
            'storeManager' => $storeManager
        ]);
    }

    public function testGetConfigParam()
    {
        $expected = 'authorization';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/global/request_type', ScopeInterface::SCOPE_STORE, null, $expected]
                ]
            );
        $result = $this->base->getConfigParam('request_type');
        $this->assertEquals($expected, $result);
    }

    public function testGetConfigParamAllStores()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/global/mid', ScopeInterface::SCOPE_STORE, 'de', '12345'],
                    ['payone_general/global/mid', ScopeInterface::SCOPE_STORE, 'en', '23456'],
                    ['payone_general/global/mid', ScopeInterface::SCOPE_STORE, 'fr', '12345'],
                    ['payone_general/global/mid', ScopeInterface::SCOPE_STORE, 'nl', '34567'],
                ]
            );
        $result = $this->base->getConfigParamAllStores('mid');
        $expected = ['12345', '23456', '34567'];
        $this->assertEquals($expected, $result);
    }

    public function testGetRequestParameter()
    {
        $expected = 'value';
        $result = $this->base->getRequestParameter('param');
        $this->assertEquals($expected, $result);
    }
}
