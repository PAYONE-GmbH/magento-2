<?php

namespace Payone\Core\Test\Unit\Controller\Amazon;

use Payone\Core\Controller\Amazon\Returned as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Checkout\Model\Session;
use Payone\Core\Helper\Checkout;
use Payone\Core\Helper\Order;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Payment;
use Magento\Checkout\Model\Type\Onepage;
use Payone\Core\Model\Api\Request\Genericpayment\GetCheckoutSession;
use Payone\Core\Model\Paypal\ReturnHandler;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

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
     * @var ReturnHandler
     */
    private $returnHandler;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var GetCheckoutSession|\PHPUnit\Framework\MockObject\MockObject
     */
    private $getCheckoutSession;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $resultRedirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $resultRedirect->method('setPath')->willReturn($resultRedirect);

        $resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $resultFactory->method('create')->willReturn($resultRedirect);

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getMessageManager')->willReturn($messageManager);

        $this->getCheckoutSession = $this->getMockBuilder(GetCheckoutSession::class)->disableOriginalConstructor()->getMock();

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getEmail')->willReturn('testing@payone.de');

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getBillingAddress',
                'setCustomerIsGuest',
                'getPayment',
                'setPayment',
                'collectTotals',
                'save',
            ])
            ->addMethods([
                'setCustomerId',
                'setCustomerEmail',
                'setCustomerGroupId',
                'setInventoryProcessed',
            ])
            ->getMock();
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('setCustomerId')->willReturn($quote);
        $quote->method('setCustomerEmail')->willReturn($quote);
        $quote->method('setCustomerIsGuest')->willReturn($quote);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('collectTotals')->willReturn($quote);

        $orderHelper = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $orderHelper->method('updateAddresses')->willReturn($quote);
        
        $checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();
        $checkoutHelper->method('getCurrentCheckoutMethod')->willReturn(Onepage::METHOD_GUEST);
        
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getQuote'
            ])
            ->addMethods([
                'getPayoneWorkorderId',
                'setIsPayonePayPalExpress',
                'getPayonePayPalExpressRetry',
                'unsPayonePayPalExpressRetry',
                'setPayoneQuoteAddressHash',
                'setPayoneExpressAddressResponse',
            ])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->returnHandler = $this->getMockBuilder(ReturnHandler::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'returnHandler' => $this->returnHandler,
            'getCheckoutSession' => $this->getCheckoutSession,
            'orderHelper' => $orderHelper,
            'checkoutHelper' => $checkoutHelper,
        ]);
    }

    public function testExecute()
    {
        $this->checkoutSession->method('getPayoneWorkorderId')->willReturn('12345');

        $response = ['status' => 'OK'];

        $this->getCheckoutSession->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteException()
    {
        $this->checkoutSession->method('getPayoneWorkorderId')->willReturn('12345');

        $response = ['status' => 'ERROR'];

        $this->getCheckoutSession->method('sendRequest')->willReturn($response);

        #$exception = new \Exception;
        #$this->returnHandler->expects($this->classToTest->method('handleReturn')->willThrowException($exception));

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecutePayPal()
    {
        $this->checkoutSession->method('getPayoneWorkorderId')->willReturn(null);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
