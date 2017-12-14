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

namespace Payone\Core\Test\Unit\Controller\Paypal;

use Payone\Core\Controller\Paypal\Express as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress;
use Magento\Checkout\Helper\Data;
use Magento\Customer\Model\Session as CustomerSession;
use Payone\Core\Helper\Payment;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\App\Response\Redirect as RedirectResponse;
use Magento\Framework\App\Console\Response;
use Magento\Framework\App\ResponseInterface;
use Magento\Quote\Model\Quote\Payment as CorePayment;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ExpressTest extends BaseTestCase
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
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var CustomerSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var PayPalExpress|\PHPUnit_Framework_MockObject_MockObject
     */
    private $genericRequest;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $resultRedirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $resultRedirect->method('setPath')->willReturn($resultRedirect);

        $resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $resultFactory->method('create')->willReturn($resultRedirect);

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $redirectResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $redirect = $this->getMockBuilder(RedirectResponse::class)->disableOriginalConstructor()->getMock();
        $redirect->method('redirect')->willReturn($redirectResponse);

        $response = $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getMessageManager')->willReturn($messageManager);
        $context->method('getRedirect')->willReturn($redirect);
        $context->method('getResponse')->willReturn($response);

        $payment = $this->getMockBuilder(CorePayment::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('hasItems')->willReturn(true);
        $quote->method('getCheckoutMethod')->willReturn(false);
        $quote->method('getStoreId')->willReturn(15);
        $quote->method('getPayment')->willReturn($payment);

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->genericRequest = $this->getMockBuilder(PayPalExpress::class)->disableOriginalConstructor()->getMock();
        $this->checkoutHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)->disableOriginalConstructor()->getMock();
        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $checkoutSession,
            'genericRequest' => $this->genericRequest,
            'checkoutHelper' => $this->checkoutHelper,
            'customerSession' => $this->customerSession,
            'paymentHelper' => $this->paymentHelper
        ]);
    }

    public function testExecuteNotActivated()
    {
        $this->paymentHelper->method('isPayPalExpressActive')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteLoginNeeded()
    {
        $this->paymentHelper->method('isPayPalExpressActive')->willReturn(true);
        $this->checkoutHelper->method('isAllowedGuestCheckout')->willReturn(false);

        $customer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();
        $customer->method('getId')->willReturn(null);

        $this->customerSession->method('getCustomerDataObject')->willReturn($customer);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteStatusError()
    {
        $this->paymentHelper->method('isPayPalExpressActive')->willReturn(true);
        $this->checkoutHelper->method('isAllowedGuestCheckout')->willReturn(false);

        $customer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();
        $customer->method('getId')->willReturn(15);

        $this->customerSession->method('getCustomerDataObject')->willReturn($customer);

        $response = ['status' => 'ERROR', 'customermessage' => 'An error occured'];
        $this->genericRequest->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteStatusErrorGuestAllowed()
    {
        $this->paymentHelper->method('isPayPalExpressActive')->willReturn(true);
        $this->checkoutHelper->method('isAllowedGuestCheckout')->willReturn(true);

        $customer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();
        $customer->method('getId')->willReturn(null);

        $this->customerSession->method('getCustomerDataObject')->willReturn($customer);

        $response = ['status' => 'ERROR', 'customermessage' => 'An error occured'];
        $this->genericRequest->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteStatusRedirect()
    {
        $this->paymentHelper->method('isPayPalExpressActive')->willReturn(true);
        $this->checkoutHelper->method('isAllowedGuestCheckout')->willReturn(false);

        $customer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();
        $customer->method('getId')->willReturn(15);

        $this->customerSession->method('getCustomerDataObject')->willReturn($customer);

        $response = ['status' => 'REDIRECT', 'workorderid' => '12345', 'redirecturl' => 'http://redirect.org'];
        $this->genericRequest->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }
}
