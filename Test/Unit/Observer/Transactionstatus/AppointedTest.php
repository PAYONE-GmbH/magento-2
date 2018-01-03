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

namespace Payone\Core\Test\Unit\Observer\Transactionstatus;

use Payone\Core\Observer\Transactionstatus\Appointed as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Payone\Core\Model\Methods\Creditcard;
use Magento\Sales\Model\Order\Payment;
use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Payone\Core\Helper\Base;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class AppointedTest extends BaseTestCase
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
     * @var OrderSender|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderSender;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->orderSender = $this->getMockBuilder(OrderSender::class)->disableOriginalConstructor()->getMock();

        $invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();

        $invoiceService = $this->getMockBuilder(InvoiceService::class)->disableOriginalConstructor()->getMock();
        $invoiceService->method('prepareInvoice')->willReturn($invoice);

        $baseHelper = $this->getMockBuilder(Base::class)->disableOriginalConstructor()->getMock();
        $baseHelper->method('getConfigParam')->willReturn('1');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'orderSender' => $this->orderSender,
            'invoiceService' => $invoiceService,
            'baseHelper' => $baseHelper
        ]);
    }

    public function testExecute()
    {
        $method = $this->getMockBuilder(Creditcard::class)->disableOriginalConstructor()->getMock();
        $method->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getLastTransId')->willReturn('123');
        $payment->method('getMethodInstance')->willReturn($method);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayment', 'getEmailSent', 'getPayoneAuthmode', 'save'])
            ->getMock();
        $order->method('getPayment')->willReturn($payment);
        $order->method('getPayoneAuthmode')->willReturn('authorization');

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $observer->method('getOrder')->willReturn($order);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $exception = new \Exception();
        $this->orderSender->method('send')->willThrowException($exception);

        $method = $this->getMockBuilder(Creditcard::class)->disableOriginalConstructor()->getMock();
        $method->method('getCode')->willReturn(PayoneConfig::METHOD_ADVANCE_PAYMENT);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getLastTransId')->willReturn('123');
        $payment->method('getMethodInstance')->willReturn($method);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayment', 'getEmailSent', 'getPayoneAuthmode', 'save'])
            ->getMock();
        $order->method('getPayment')->willReturn($payment);
        $order->method('getPayoneAuthmode')->willReturn('authorization');

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $observer->method('getOrder')->willReturn($order);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteNoOrder()
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $observer->method('getOrder')->willReturn(null);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }
}
