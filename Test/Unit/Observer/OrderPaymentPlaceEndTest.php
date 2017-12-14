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

namespace Payone\Core\Test\Unit\Observer;

use Payone\Core\Observer\OrderPaymentPlaceEnd as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Payone\Core\Helper\Consumerscore;
use Magento\Framework\Event;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class OrderPaymentPlaceEndTest extends BaseTestCase
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

        $consumerscoreHelper = $this->getMockBuilder(Consumerscore::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalDataEntry', 'getConsumerscoreSampleCounter', 'incrementConsumerscoreSampleCounter'])
            ->getMock();
        $consumerscoreHelper->method('getAdditionalDataEntry')->willReturn(true);
        $consumerscoreHelper->method('getConsumerscoreSampleCounter')->willReturn(0);
        $consumerscoreHelper->method('incrementConsumerscoreSampleCounter')->willReturn(1);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'consumerscoreHelper' => $consumerscoreHelper
        ]);
    }

    public function testExecute()
    {
        $expectedStatus = 'new';

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);
        $paymentMethod->method('getConfigData')->willReturn($expectedStatus);

        $order = $this->objectManager->getObject(Order::class);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);
        $payment->method('getOrder')->willReturn($order);

        $event = $this->getMockBuilder(Event::class)->disableOriginalConstructor()->setMethods(['getPayment'])->getMock();
        $event->method('getPayment')->willReturn($payment);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);

        $this->assertEquals(Order::STATE_NEW, $order->getState());
        $this->assertEquals($expectedStatus, $order->getStatus());
    }
}
