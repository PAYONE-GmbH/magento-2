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

use Payone\Core\Observer\PredispatchCheckoutIndex as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\NoSuchEntityException;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class PredispatchCheckoutIndexTest extends BaseTestCase
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
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPayoneCustomerIsRedirected',
                'getLastOrderId',
                'restoreQuote',
                'unsLastQuoteId',
                'unsLastSuccessQuoteId',
                'unsLastOrderId',
                'unsLastRealOrderId',
                'unsPayoneCustomerIsRedirected',
                'setIsPayoneRedirectCancellation'
            ])
            ->getMock();
        $this->checkoutSession->method('getPayoneCustomerIsRedirected')->willReturn(true);
        $this->checkoutSession->method('getLastOrderId')->willReturn('123');
        $this->checkoutSession->method('unsLastQuoteId')->willReturn($this->checkoutSession);
        $this->checkoutSession->method('unsLastSuccessQuoteId')->willReturn($this->checkoutSession);
        $this->checkoutSession->method('unsLastOrderId')->willReturn($this->checkoutSession);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('load')->willReturn($order);
        $order->method('cancel')->willReturn($order);

        $orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $orderFactory->method('create')->willReturn($order);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $this->checkoutSession,
            'orderFactory' => $orderFactory,
        ]);
    }

    public function testExecute()
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteLocalizedException()
    {
        $exception = $this->objectManager->getObject(NoSuchEntityException::class);
        $this->checkoutSession->expects($this->once())->method('restoreQuote')->willThrowException($exception);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $exception = $this->objectManager->getObject(\Exception::class);
        $this->checkoutSession->expects($this->once())->method('restoreQuote')->willThrowException($exception);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }
}
