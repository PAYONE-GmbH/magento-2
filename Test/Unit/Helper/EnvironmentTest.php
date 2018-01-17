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

use Payone\Core\Helper\Environment;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Model\Environment\RemoteAddress;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class EnvironmentTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $remoteAddress = $this->getMockBuilder(RemoteAddress::class)->disableOriginalConstructor()->getMock();
        $remoteAddress->method('getRemoteAddress')->willReturn('192.168.1.100');

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig, 'remoteAddress' => $remoteAddress]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->environment = $this->objectManager->getObject(Environment::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'remoteAddress' => $remoteAddress
        ]);
    }

    public function testGetEncoding()
    {
        $result = $this->environment->getEncoding();
        $expected = 'UTF-8';
        $this->assertEquals($expected, $result);
    }

    public function testGetRemoteIp()
    {
        $expected = '192.168.1.100';

        $result = $this->environment->getRemoteIp();
        $this->assertEquals($expected, $result);
    }

    public function testIsRemoteIpValid()
    {
        $sWhitelist = "127.0.0.1\n166.7.1.*\n192.168.*.*\n217.8.19.7";

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_misc/processing/valid_ips', ScopeInterface::SCOPE_STORE, null, $sWhitelist],
                    ['payone_misc/processing/proxy_mode', ScopeInterface::SCOPE_STORE, null, 1]
                ]
            );

        $result = $this->environment->isRemoteIpValid();
        $this->assertTrue($result);
    }

    public function testIsRemoteIpNotValid()
    {
        $sWhitelist = "127.0.0.1\n166.7.1.*\n217.8.19.7";

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_misc/processing/valid_ips', ScopeInterface::SCOPE_STORE, null, $sWhitelist]
                ]
            );

        $result = $this->environment->isRemoteIpValid();
        $this->assertFalse($result);
    }

    public function testIsRemoteIpDirectValid()
    {
        $sWhitelist = "127.0.0.1\n166.7.1.*\n217.8.19.7\n192.168.1.100";

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_misc/processing/valid_ips', ScopeInterface::SCOPE_STORE, null, $sWhitelist]
                ]
            );

        $result = $this->environment->isRemoteIpValid();
        $this->assertTrue($result);
    }
}
