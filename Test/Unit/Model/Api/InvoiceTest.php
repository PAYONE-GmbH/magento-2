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

namespace Payone\Core\Test\Unit\Model\Api;

use Payone\Core\Model\Api\Invoice as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Helper\AmastyGiftcard;
use Payone\Core\Model\Api\Request\Authorization;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Item;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Store\Model\Store;

class InvoiceTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Toolkit
     */
    private $toolkitHelper;

    /**
     * @var AmastyGiftcard
     */
    private $amastyHelper;

    /**
     * @var Store
     */
    private $store;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $this->toolkitHelper->method('getInvoiceAppendix')->willReturn('invoice appendix');
        $this->toolkitHelper->expects($this->any())
            ->method('formatNumber')
            ->willReturnMap([
                [100, 2, '100.00'],
                [5, 2, '5.00'],
                [-5, 2, '-5.00'],
                [-7, 2, '-7.00'],
                [90, 2, '90.00'],
                [105, 2, '105.00'],
            ]);

        $this->amastyHelper = $this->getMockBuilder(AmastyGiftcard::class)->disableOriginalConstructor()->getMock();

        $this->store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $this->store->method('getCode')->willReturn('test');

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'toolkitHelper' => $this->toolkitHelper,
            'amastyHelper' => $this->amastyHelper,
        ]);
    }

    /**
     * Returns item mock object
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getItemMock($type = Item::class)
    {
        $item = $this->getMockBuilder($type)
            ->disableOriginalConstructor()
            ->setMethods(['isDummy', 'getProductId', 'getQtyOrdered', 'getSku', 'getPriceInclTax', 'getBasePriceInclTax', 'getName', 'getTaxPercent', 'getOrigData', 'getParentItemId', 'getQty'])
            ->getMock();
        $item->method('isDummy')->willReturn(false);
        $item->method('getProductId')->willReturn('12345');
        $item->method('getQtyOrdered')->willReturn('1');
        $item->method('getQty')->willReturn('1');
        $item->method('getSku')->willReturn('test_123');
        $item->method('getPriceInclTax')->willReturn('120');
        $item->method('getBasePriceInclTax')->willReturn('100');
        $item->method('getName')->willReturn('Test product');
        $item->method('getTaxPercent')->willReturn('19');
        $item->method('getOrigData')->willReturn('1');
        $item->method('getParentItemId')->willReturn(null);
        return $item;
    }

    public function testAddProductInfo()
    {
        $this->toolkitHelper->method('getConfigParam')->willReturn('sku');
        $this->amastyHelper->method('hasAmastyGiftcards')->willReturn(true);
        $this->amastyHelper->method('getAmastyGiftCards')->willReturn([['base_gift_amount' => 5, 'gift_amount' => 5, 'code' => 'TEST']]);

        $authorization = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();
        
        $items = [$this->getItemMock()];
        
        $expected = 110;

        $order = $this->getMockBuilder(Order::class)
            ->setMethods([
                'getAllItems',
                'getBaseShippingInclTax',
                'getBaseDiscountAmount',
                'getCouponCode',
                'getBaseGrandTotal',
                'getStore',
                'getGiftCards'
            ])
            ->disableOriginalConstructor()->getMock();
        $order->method('getAllItems')->willReturn($items);
        $order->method('getBaseShippingInclTax')->willReturn(-5);
        $order->method('getBaseDiscountAmount')->willReturn(-5);
        $order->method('getCouponCode')->willReturn('test');
        $order->method('getBaseGrandTotal')->willReturn(115);
        $order->method('getStore')->willReturn($this->store);
        $order->method('getGiftCards')->willReturn('[{"i":365,"c":"testcode","a":10,"ba":10,"authorized":10}]');

        $result = $this->classToTest->addProductInfo($authorization, $order, false);
        $this->assertEquals($expected, $result);
    }

    public function testAddProductInfoDisplay()
    {
        $this->toolkitHelper->method('getConfigParam')->willReturn('display');
        $this->amastyHelper->method('hasAmastyGiftcards')->willReturn(true);
        $this->amastyHelper->method('getAmastyGiftCards')->willReturn([['base_gift_amount' => 5, 'gift_amount' => 5, 'code' => 'TEST']]);

        $authorization = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $items = [$this->getItemMock()];

        $expected = 106;

        $order = $this->getMockBuilder(Order::class)
            ->setMethods([
                'getAllItems',
                'getBaseShippingInclTax',
                'getShippingInclTax',
                'getBaseDiscountAmount',
                'getDiscountAmount',
                'getCouponCode',
                'getBaseGrandTotal',
                'getGrandTotal',
                'getStore',
                'getGiftCards'
            ])->disableOriginalConstructor()->getMock();
        $order->method('getAllItems')->willReturn($items);
        $order->method('getBaseShippingInclTax')->willReturn(-5);
        $order->method('getShippingInclTax')->willReturn(-7);
        $order->method('getBaseDiscountAmount')->willReturn(-5);
        $order->method('getDiscountAmount')->willReturn(-7);
        $order->method('getCouponCode')->willReturn('test');
        $order->method('getBaseGrandTotal')->willReturn(113);
        $order->method('getGrandTotal')->willReturn(113);
        $order->method('getStore')->willReturn($this->store);
        $order->method('getGiftCards')->willReturn('[{"i":365,"c":"testcode","a":10,"ba":10,"authorized":10}]');

        $result = $this->classToTest->addProductInfo($authorization, $order, false);
        $this->assertEquals($expected, $result);
    }

    public function testAddProductInfoNoAmasty()
    {
        $this->toolkitHelper->method('getConfigParam')->willReturn('display');
        $this->amastyHelper->method('hasAmastyGiftcards')->willReturn(false);
        $this->amastyHelper->method('getAmastyGiftCards')->willReturn([]);

        $authorization = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $items = [$this->getItemMock()];

        $expected = 101;

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getAllItems')->willReturn($items);
        $order->method('getBaseShippingInclTax')->willReturn(-5);
        $order->method('getShippingInclTax')->willReturn(-7);
        $order->method('getBaseDiscountAmount')->willReturn(-5);
        $order->method('getDiscountAmount')->willReturn(-7);
        $order->method('getCouponCode')->willReturn('test');
        $order->method('getBaseGrandTotal')->willReturn($expected);
        $order->method('getGrandTotal')->willReturn($expected);
        $order->method('getStore')->willReturn($this->store);

        $result = $this->classToTest->addProductInfo($authorization, $order, false);
        $this->assertEquals($expected, $result);
    }

    public function testAddProductInfoSurcharge()
    {
        $this->toolkitHelper->method('getConfigParam')->willReturn('sku');
        $this->amastyHelper->method('hasAmastyGiftcards')->willReturn(false);
        $this->amastyHelper->method('getAmastyGiftCards')->willReturn([]);

        $authorization = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $items = [$this->getItemMock()];

        $expected = 110;

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getAllItems')->willReturn($items);
        $order->method('getBaseShippingInclTax')->willReturn(5);
        $order->method('getBaseDiscountAmount')->willReturn(-5);
        $order->method('getCouponCode')->willReturn('test');
        $order->method('getGrandTotal')->willReturn($expected);
        $order->method('getStore')->willReturn($this->store);

        $positions = ['12345test_123' => '1', 'delcost' => '10', 'discount' => '-10'];

        $result = $this->classToTest->addProductInfo($authorization, $order, $positions, true);
        $this->assertEquals($expected, $result);
    }

    public function testAddProductInfoException()
    {
        $this->toolkitHelper->method('getConfigParam')->willReturn('sku');
        $this->amastyHelper->method('hasAmastyGiftcards')->willReturn(false);
        $this->amastyHelper->method('getAmastyGiftCards')->willReturn([]);

        $authorization = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $items = [$this->getItemMock()];

        $expected = 100;

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getAllItems')->willReturn($items);
        $order->method('getBaseShippingInclTax')->willReturn(5);
        $order->method('getBaseDiscountAmount')->willReturn(-5);
        $order->method('getCouponCode')->willReturn('test');
        $order->method('getGrandTotal')->willReturn($expected);
        $order->method('getStore')->willReturn($this->store);

        $positions = ['12345test_123' => 1.7];

        $this->expectException(\InvalidArgumentException::class);
        $result = $this->classToTest->addProductInfo($authorization, $order, $positions);
    }

    public function testAddProductInfoQuote()
    {
        $this->toolkitHelper->method('getConfigParam')->willReturn('display');
        $this->amastyHelper->method('hasAmastyGiftcards')->willReturn(false);
        $this->amastyHelper->method('getAmastyGiftCards')->willReturn([]);

        $authorization = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $items = [$this->getItemMock(\Magento\Quote\Model\Quote\Item::class)];

        $expected = 125;

        $order = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAllItems',
                'getBaseShippingInclTax',
                'getBaseDiscountAmount',
                'getCouponCode',
                'getBaseGrandTotal',
                'getStore',
            ])
            ->getMock();
        $order->method('getAllItems')->willReturn($items);
        $order->method('getBaseShippingInclTax')->willReturn(5);
        $order->method('getBaseDiscountAmount')->willReturn(-5);
        $order->method('getCouponCode')->willReturn('test');
        $order->method('getBaseGrandTotal')->willReturn($expected);
        $order->method('getStore')->willReturn($this->store);

        $result = $this->classToTest->addProductInfo($authorization, $order, false, false, 5);
        $this->assertEquals($expected, $result);
    }
}
