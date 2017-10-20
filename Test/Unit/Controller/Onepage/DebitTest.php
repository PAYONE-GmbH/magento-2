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
use Payone\Core\Controller\Onepage\Debit as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Payone\Core\Model\Api\Request\Managemandate;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote\Payment;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Store\App\Response\Redirect as RedirectResponse;
use Magento\Framework\App\Console\Response;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class DebitTest extends BaseTestCase
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

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Managemandate|\PHPUnit_Framework_MockObject_MockObject
     */
    private $managemandateRequest;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $redirectResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $redirect = $this->getMockBuilder(RedirectResponse::class)->disableOriginalConstructor()->getMock();
        $redirect->method('redirect')->willReturn($redirectResponse);

        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect'])
            ->getMock();

        $url = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRedirect')->willReturn($redirect);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getResponse')->willReturn($response);
        $context->method('getUrl')->willReturn($url);

        $this->payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->quote->method('getPayment')->willReturn($this->payment);
        $this->quote->method('getId')->willReturn('12345');

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPayoneMandate', 'getPayoneMandate', 'setPayoneDebitError', 'getQuote'])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);

        $page = $this->getMockBuilder(Page::class)->disableOriginalConstructor()->getMock();

        $pageFactory = $this->getMockBuilder(PageFactory::class)->disableOriginalConstructor()->getMock();
        $pageFactory->method('create')->willReturn($page);
        $this->managemandateRequest = $this->getMockBuilder(Managemandate::class)->disableOriginalConstructor()->getMock();
        $typeOnepage = $this->getMockBuilder(Onepage::class)->disableOriginalConstructor()->getMock();
        $typeOnepage->method('getCheckoutMethod')->willReturn('register');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'managemandateRequest' => $this->managemandateRequest,
            'pageFactory' => $pageFactory,
            'typeOnepage' => $typeOnepage
        ]);
    }

    public function testExecuteNoPayment()
    {
        $this->payment->method('getMethodInstance')->willReturn(null);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteSuccess()
    {
        $paymentMethod = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getMethodInstance')->willReturn($paymentMethod);
        $this->request->method('getParam')->willReturn(0);
        $this->checkoutSession->method('getPayoneMandate')->willReturn(['dummyMandate']);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteMandateMismatch()
    {
        $paymentMethod = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCustomConfigParam')->willReturn(true);
        $this->payment->method('getMethodInstance')->willReturn($paymentMethod);
        $this->request->method('getParam')->willReturn(1);
        $this->checkoutSession->method('getPayoneMandate')->willReturn(['mandate_identification' => 5]);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteMandate()
    {
        $paymentMethod = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCustomConfigParam')->willReturn(true);
        $this->payment->method('getMethodInstance')->willReturn($paymentMethod);
        $this->request->method('getParam')->willReturn(1);
        $this->checkoutSession->method('getPayoneMandate')->willReturn(null);
        $this->managemandateRequest->method('sendRequest')->willReturn(['status' => 'VALID', 'mandate_status' => 'valid']);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteMandatePending()
    {
        $paymentMethod = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCustomConfigParam')->willReturn(true);
        $this->payment->method('getMethodInstance')->willReturn($paymentMethod);
        $this->request->method('getParam')->willReturn(1);
        $this->checkoutSession->method('getPayoneMandate')->willReturn(null);
        $this->managemandateRequest->method('sendRequest')->willReturn(['status' => 'VALID', 'mandate_status' => 'pending']);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Page::class, $result);
    }

    public function testExecuteMandateError()
    {
        $paymentMethod = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCustomConfigParam')->willReturn(true);
        $this->payment->method('getMethodInstance')->willReturn($paymentMethod);
        $this->request->method('getParam')->willReturn(1);
        $this->checkoutSession->method('getPayoneMandate')->willReturn(null);
        $this->managemandateRequest->method('sendRequest')->willReturn(['status' => 'ERROR', 'errorcode' => '123', 'customermessage' => 'error']);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Page::class, $result);
    }
}
