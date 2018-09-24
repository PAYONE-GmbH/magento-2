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

namespace Payone\Core\Test\Unit\Controller\Mandate;

use Payone\Core\Controller\Mandate\Download as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Api\Request\Getfile;
use Payone\Core\Helper\Payment;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment AS OrderPayment;
use Payone\Core\Model\Methods\Debit;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class DownloadTest extends BaseTestCase
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

        $payonePayment = $this->getMockBuilder(Debit::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(OrderPayment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($payonePayment);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneMandateId', 'getPayment'])
            ->getMock();
        $order->method('getPayoneMandateId')->willReturn('12345');
        $order->method('getPayment')->willReturn($payment);

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        $getfileRequest = $this->getMockBuilder(Getfile::class)->disableOriginalConstructor()->getMock();
        $getfileRequest->method('sendRequest')->willReturn('Mandate Test Text');

        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('isMandateManagementDownloadActive')->willReturn(true);

        $rawResponse = $this->getMockBuilder(Raw::class)->disableOriginalConstructor()->getMock();
        $resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $resultRawFactory->method('create')->willReturn($rawResponse);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $checkoutSession,
            'getfileRequest' => $getfileRequest,
            'paymentHelper' => $paymentHelper,
            'resultRawFactory' => $resultRawFactory
        ]);
    }

    public function testExecute()
    {
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }
}
