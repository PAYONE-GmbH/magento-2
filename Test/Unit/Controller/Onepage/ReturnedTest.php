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

namespace Payone\Core\Test\Unit\Controller\Onepage;

use Magento\Quote\Model\Quote;
use Payone\Core\Controller\Onepage\Returned as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Store\App\Response\Redirect as RedirectResponse;
use Magento\Framework\App\Console\Response;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\UrlInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Payment;
use Payone\Core\Helper\Database;
use Payone\Core\Model\Entities\TransactionStatusFactory;
use Payone\Core\Model\Entities\TransactionStatus;

class ReturnedTest extends BaseTestCase
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

        $redirectResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $redirect = $this->getMockBuilder(RedirectResponse::class)->disableOriginalConstructor()->getMock();
        $redirect->method('redirect')->willReturn($redirectResponse);

        $response = $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock();

        $url = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRedirect')->willReturn($redirect);
        $context->method('getResponse')->willReturn($response);
        $context->method('getUrl')->willReturn($url);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getLastRealOrder',
                'setLastRealOrderId',
                'getPayoneCanceledOrder',
                'unsPayoneCanceledOrder',
                'unsPayoneCustomerIsRedirected',
                'setPayoneCreatingSubstituteOrder',
                'unsPayoneCreatingSubstituteOrder',
                'setLastOrderId',
                'getQuote'
            ])
            ->getMock();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $quoteRepository = $this->getMockBuilder(QuoteRepository::class)->disableOriginalConstructor()->getMock();
        $quoteRepository->method('get')->willReturn($quote);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getId')->willReturn(123);

        $order = $this->getMockBuilder(OrderCore::class)->disableOriginalConstructor()->getMock();
        $order->method('getPayment')->willReturn($payment);

        $orderRepository = $this->getMockBuilder(OrderRepository::class)->disableOriginalConstructor()->getMock();
        $orderRepository->method('get')->willReturn($order);

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getNotHandledTransactionsByOrderId')->willReturn([['id' => 5]]);

        $status = $this->getMockBuilder(TransactionStatus::class)->disableOriginalConstructor()->getMock();

        $statusFactory = $this->getMockBuilder(TransactionStatusFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $statusFactory->method('create')->willReturn($status);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'quoteRepository' => $quoteRepository,
            'orderRepository' => $orderRepository,
            'databaseHelper' => $databaseHelper,
            'statusFactory' => $statusFactory
        ]);
    }

    public function testExecute()
    {
        $order = $this->getMockBuilder(OrderCore::class)->disableOriginalConstructor()->getMock();
        $order->method('getId')->willReturn(null);
        $order->method('getStatus')->willReturn(OrderCore::STATE_COMPLETE);

        $this->checkoutSession->method('getLastRealOrder')->willReturn($order);
        $this->checkoutSession->method('getPayoneCanceledOrder')->willReturn(null);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteSubstitute()
    {
        $order = $this->getMockBuilder(OrderCore::class)->disableOriginalConstructor()->getMock();
        $order->method('getId')->willReturn(null);
        $order->method('getStatus')->willReturn(OrderCore::STATE_CANCELED);
        $order->method('getQuoteId')->willReturn('123');
        $order->method('getData')->willReturn(['payone_txid' => '12345']);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('setIsActive')->willReturn($quote);

        $this->checkoutSession->method('getLastRealOrder')->willReturn($order);
        $this->checkoutSession->method('getPayoneCanceledOrder')->willReturn(123);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }
}
