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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Plugins;

use Magento\Sales\Model\Order;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Methods\Creditcard;
use Payone\Core\Model\Plugins\State as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order\Config;

class StateTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getMagentoVersion')->willReturn('2.3.1');
        
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $shopHelper
        ]);
    }

    public function testAroundCheck()
    {
        $method = $this->getMockBuilder(Creditcard::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMethodInstance'])
            ->getMock();
        $payment->method('getMethodInstance')->willReturn($method);

        $orderConfig = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $orderConfig->method('getStateDefaultStatus')->willReturn('state');
        
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPayment', 'getState', 'isCanceled', 'canUnhold', 'canInvoice', 'canCreditmemo', 'canShip', 'getIsVirtual', 'getConfig'])
            ->addMethods(['getIsInProcess'])
            ->getMock();
        $order->method('getPayment')->willReturn($payment);
        $order->method('getState')->willReturn(Order::STATE_NEW);
        $order->method('getIsInProcess')->willReturn(true);
        $order->method('isCanceled')->willReturn(false);
        $order->method('canUnhold')->willReturn(false);
        $order->method('canInvoice')->willReturn(false);
        $order->method('canCreditmemo')->willReturn(false);
        $order->method('canShip')->willReturn(false);
        $order->method('getIsVirtual')->willReturn(1);
        $order->method('getConfig')->willReturn($orderConfig);

        $subject = $this->getMockBuilder(State::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($subject) {
            return $subject;
        };

        $result = $this->classToTest->aroundCheck($subject, $proceed, $order);
        $this->assertInstanceOf(State::class, $result);
    }

    public function testAroundCheckComplete()
    {
        $method = $this->getMockBuilder(Creditcard::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMethodInstance'])
            ->getMock();
        $payment->method('getMethodInstance')->willReturn($method);

        $orderConfig = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $orderConfig->method('getStateDefaultStatus')->willReturn('state');

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPayment', 'getState', 'isCanceled', 'canUnhold', 'canInvoice', 'canCreditmemo', 'canShip', 'getIsVirtual', 'getConfig'])
            ->addMethods(['getIsInProcess'])
            ->getMock();
        $order->method('getPayment')->willReturn($payment);
        $order->method('getState')->willReturn(Order::STATE_NEW);
        $order->method('getIsInProcess')->willReturn(true);
        $order->method('isCanceled')->willReturn(false);
        $order->method('canUnhold')->willReturn(false);
        $order->method('canInvoice')->willReturn(false);
        $order->method('canCreditmemo')->willReturn(true);
        $order->method('canShip')->willReturn(false);
        $order->method('getIsVirtual')->willReturn(1);
        $order->method('getConfig')->willReturn($orderConfig);

        $subject = $this->getMockBuilder(State::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($subject) {
            return $subject;
        };

        $result = $this->classToTest->aroundCheck($subject, $proceed, $order);
        $this->assertInstanceOf(State::class, $result);
    }

    public function testAroundCheckNoPayonePayment()
    {
        $method = $this->getMockBuilder(AbstractMethod::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMethodInstance'])
            ->getMock();
        $payment->method('getMethodInstance')->willReturn($method);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getPayment')->willReturn($payment);

        $subject = $this->getMockBuilder(State::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($subject) {
            return $subject;
        };

        $result = $this->classToTest->aroundCheck($subject, $proceed, $order);
        $this->assertInstanceOf(State::class, $result);
    }
}
