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

namespace Payone\Core\Test\Unit\Model\Handler;

use Magento\Sales\Model\Order;
use Payone\Core\Helper\Database;
use Payone\Core\Model\Handler\SubstituteOrder as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Payment;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Entities\TransactionStatusFactory;
use Payone\Core\Model\Entities\TransactionStatus;

class SubstituteOrderTest extends BaseTestCase
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

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('setIsActive')->willReturn($quote);

        $quoteRepository = $this->getMockBuilder(QuoteRepository::class)->disableOriginalConstructor()->getMock();
        $quoteRepository->method('get')->willReturn($quote);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getId')->willReturn(123);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getPayment')->willReturn($payment);

        $orderRepository = $this->getMockBuilder(OrderRepository::class)->disableOriginalConstructor()->getMock();
        $orderRepository->method('get')->willReturn($order);

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $transactions = [
            ['id' => '5']
        ];

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getNotHandledTransactionsByOrderId')->willReturn($transactions);

        $status = $this->getMockBuilder(TransactionStatus::class)->disableOriginalConstructor()->getMock();

        $statusFactory = $this->getMockBuilder(TransactionStatusFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $statusFactory->method('create')->willReturn($status);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'quoteRepository' => $quoteRepository,
            'orderRepository' => $orderRepository,
            'checkoutSession' => $checkoutSession,
            'databaseHelper' => $databaseHelper,
            'statusFactory' => $statusFactory
        ]);
    }

    public function testCreateSubstituteOrder()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getData')->willReturn(['payone_test' => 'test']);

        $result = $this->classToTest->createSubstituteOrder($order, true);
        $this->assertInstanceOf(Order::class, $result);
    }
}
