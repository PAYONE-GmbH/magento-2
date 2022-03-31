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

namespace Payone\Core\Test\Unit\Model\Handler;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Payone\Core\Model\Handler\Cancellation as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\ResourceModel\TransactionStatus;

class CancellationTest extends BaseTestCase
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
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var TransactionStatus|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionStatus;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $currentQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $currentQuote->method('getId')->willReturn(123);

        $oldQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $oldQuote->method('getId')->willReturn(321);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPayoneCustomerIsRedirected',
                'unsPayoneCustomerIsRedirected',
                'getQuote',
                'getLastOrderId',
                'unsLastOrderId',
                'getLastQuoteId',
                'unsLastQuoteId',
                'unsLastSuccessQuoteId',
                'unsLastRealOrderId',
                'setPayoneCanceledOrder',
                'setIsPayoneRedirectCancellation',
            ])
            ->getMock();
        $checkoutSession->method('getPayoneCustomerIsRedirected')->willReturn(true);
        $checkoutSession->method('getLastOrderId')->willReturn(123);
        $checkoutSession->method('getQuote')->willReturn($currentQuote);
        $checkoutSession->method('getLastQuoteId')->willReturn(123);
        $checkoutSession->method('unsLastQuoteId')->willReturn($checkoutSession);
        $checkoutSession->method('unsLastSuccessQuoteId')->willReturn($checkoutSession);
        $checkoutSession->method('unsLastOrderId')->willReturn($checkoutSession);
        $checkoutSession->method('unsLastRealOrderId')->willReturn($checkoutSession);

        $this->order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->order->method('load')->willReturn($this->order);
        $this->order->method('getIncrementId')->willReturn(123);

        $orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $orderFactory->method('create')->willReturn($this->order);

        $quoteRepository = $this->getMockBuilder(QuoteRepository::class)->disableOriginalConstructor()->getMock();
        $quoteRepository->method('get')->willReturn($oldQuote);

        $this->transactionStatus = $this->getMockBuilder(TransactionStatus::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $checkoutSession,
            'orderFactory' => $orderFactory,
            'quoteRepository' => $quoteRepository,
            'transactionStatus' => $this->transactionStatus
        ]);
    }

    public function testHandle()
    {
        $this->transactionStatus->method('getAppointedIdByTxid')->willReturn(null);

        $result = $this->classToTest->handle();
        $this->assertNull($result);
    }

    public function testHandleHasInvoice()
    {
        $this->transactionStatus->method('getAppointedIdByTxid')->willReturn(null);
        $this->order->method('hasInvoices')->willReturn(true);

        $result = $this->classToTest->handle();
        $this->assertNull($result);
    }

    public function testHandleHasAppointed()
    {
        $this->transactionStatus->method('getAppointedIdByTxid')->willReturn('12345');

        $result = $this->classToTest->handle();
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $this->transactionStatus->method('getAppointedIdByTxid')->willReturn(null);

        $exception = new \Exception();
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->handle();
        $this->assertNull($result);
    }

    public function testExecuteLocalizedException()
    {
        $this->transactionStatus->method('getAppointedIdByTxid')->willReturn(null);

        $exception = new LocalizedException(__('An error occured'));
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->handle();
        $this->assertNull($result);
    }
}
