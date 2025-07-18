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

namespace Payone\Core\Test\Unit\Model\Source;

use Payone\Core\Model\Source\Currency as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;

class CurrencyTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;
  
    /**
     * @var Context
     */
    private $context;

    /**
     * @var RequestInterface
     */
    private $request;

    protected function setUp(): void
    {
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefaultCurrencyCode', 'getBaseCurrencyCode'])
            ->getMock();
        $store->method('getDefaultCurrencyCode')->willReturn('EUR');
        $store->method('getBaseCurrencyCode')->willReturn('USD');

        $website = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseCurrencyCode'])
            ->addMethods(['getDefaultCurrencyCode'])
            ->getMock();
        $store->method('getDefaultCurrencyCode')->willReturn('EUR');
        $store->method('getBaseCurrencyCode')->willReturn('USD');
        
        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);
        $storeManager->method('getWebsite')->willReturn($website);

        $this->request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();

        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->context->method('getStoreManager')->willReturn($storeManager);
        $this->context->method('getRequest')->willReturn($this->request);

        $objectManager = $this->getObjectManager();
        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'context' => $this->context
        ]);
    }

    public function testToOptionArray()
    {
        $result = $this->classToTest->toOptionArray();
        $this->assertCount(2, $result);
    }

    public function testToOptionArrayWebsiteParam()
    {
        $this->request->method('getParams')->willReturn(['website' => '2']);

        $result = $this->classToTest->toOptionArray();
        $this->assertCount(2, $result);
    }

    public function testToOptionArrayStoreParam()
    {
        $this->request->method('getParams')->willReturn(['store' => '2']);

        $result = $this->classToTest->toOptionArray();
        $this->assertCount(2, $result);
    }
}
