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
use Payone\Core\Controller\Onepage\Cancel as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;

class CancelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $resultRedirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $resultRedirect->method('setPath')->willReturn($resultRedirect);

        $resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $resultFactory->method('create')->willReturn($resultRedirect);

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getMessageManager')->willReturn($messageManager);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('setIsActive')->willReturn($quote);
        $quote->method('setReservedOrderId')->willReturn($quote);
        $quote->method('save')->willReturn($quote);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setIsPayoneRedirectCancellation',
                'getLastOrderId',
                'unsLastQuoteId',
                'unsLastSuccessQuoteId',
                'unsLastOrderId',
                'unsLastRealOrderId',
                'setQuoteId',
                'setLoadInactive',
                'getQuote',
                'replaceQuote',
            ])
            ->getMock();
        $this->checkoutSession->method('getLastOrderId')->willReturn('12345');
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->checkoutSession->method('unsLastQuoteId')->willReturn($this->checkoutSession);
        $this->checkoutSession->method('unsLastSuccessQuoteId')->willReturn($this->checkoutSession);
        $this->checkoutSession->method('unsLastOrderId')->willReturn($this->checkoutSession);

        $this->order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->order->method('load')->willReturn($this->order);
        $this->order->method('getQuoteId')->willReturn('12345');
        $this->order->method('cancel')->willReturn($this->order);
        $this->order->method('save')->willReturn($this->order);

        $orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $orderFactory->method('create')->willReturn($this->order);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'orderFactory' => $orderFactory
        ]);
    }

    public function testExecute()
    {
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteException()
    {
        $exception = new \Exception();
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteLocalizedException()
    {
        $exception = new LocalizedException(__('An error occured'));
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
