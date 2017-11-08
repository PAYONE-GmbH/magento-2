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

use Payone\Core\Helper\Shop;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\ProductMetadata;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ShopTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Shop
     */
    protected $shop;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $productMetadata = $this->getMockBuilder(ProductMetadata::class)->disableOriginalConstructor()->getMock();
        $productMetadata->method('getEdition')->willReturn('Community');
        $productMetadata->method('getVersion')->willReturn('2.0.0');

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getId')->willReturn(1);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);
        $this->shop = $this->objectManager->getObject(Shop::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'productMetadata' => $productMetadata
        ]);
    }

    public function testGetMagentoEdition()
    {
        $result = $this->shop->getMagentoEdition();
        $expected = ['Community', 'Enterprise'];
        $this->assertContains($result, $expected);
    }

    public function testGetMagentoVersion()
    {
        $result = $this->shop->getMagentoVersion();
        $this->assertNotEmpty($result);
    }

    public function testGetStoreId()
    {
        $result = $this->shop->getStoreId();
        $expected = 1;
        $this->assertEquals($expected, $result);
    }

    public function testGetLocale()
    {
        $expected = 'de';
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['general/locale/code', ScopeInterface::SCOPE_STORE, null, $expected]]);
        $result = $this->shop->getLocale();
        $this->assertEquals($expected, $result);
    }
}
