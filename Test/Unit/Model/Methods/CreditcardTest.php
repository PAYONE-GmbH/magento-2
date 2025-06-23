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

namespace Payone\Core\Test\Unit\Model\Methods;

use Magento\Store\Model\Store;
use Payone\Core\Helper\Shop;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Model\Methods\Creditcard as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\DataObject;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

class CreditcardTest extends BaseTestCase
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
     * @var Order\Payment
     */
    private $info;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->info = $this->getMockBuilder(Order\Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation', 'getOrder'])
            ->getMock();
        $this->info->method('getAdditionalInformation')->willReturn('info');

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('getAdditionalDataEntry')->willReturn('info');

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getConfigParam')->willReturn(true);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(123);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'toolkitHelper' => $toolkitHelper,
            'shopHelper' => $shopHelper,
            'checkoutSession' => $checkoutSession
        ]);
        $this->classToTest->setInfoInstance($this->info);
    }

    public function testGetPaymentSpecificParameters()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $expected = [
            'pseudocardpan' => 'info',
            'cardholder' => 'info'
        ];
        $this->assertEquals($expected, $result);
    }

    public function testAssignData()
    {
        $addData = [
            'saveData' => 1,
            'pseudocardpan' => '123',
            'truncatedcardpan' => '1X3'
        ];

        $data = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData'])
            ->getMock();
        $data->method('getAdditionalData')->willReturn($addData);

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('default');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getStore')->willReturn($store);

        $this->info->method('getOrder')->willReturn($order);

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testAssignDataNoOrder()
    {
        $addData = [
            'saveData' => 1,
            'pseudocardpan' => '123',
            'truncatedcardpan' => '1X3'
        ];

        $data = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData'])
            ->getMock();
        $data->method('getAdditionalData')->willReturn($addData);

        $this->info->method('getOrder')->willReturn(null);

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testAssignDataNoOrderNoStore()
    {
        $addData = [
            'saveData' => 1,
            'pseudocardpan' => '123',
            'truncatedcardpan' => '1X3'
        ];

        $data = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData'])
            ->getMock();
        $data->method('getAdditionalData')->willReturn($addData);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getStore')->willReturn(null);

        $this->info->method('getOrder')->willReturn($order);

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }
}
