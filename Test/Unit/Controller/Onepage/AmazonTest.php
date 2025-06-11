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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Controller\Onepage;

use Magento\Checkout\Helper\Data;
use Magento\Quote\Model\Quote;
use Payone\Core\Controller\Onepage\Amazon as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Payone\Core\Helper\Payment;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Message\ManagerInterface;

class AmazonTest extends BaseTestCase
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
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var CustomerSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutHelper;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customer;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $page = $this->getMockBuilder(Page::class)->disableOriginalConstructor()->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $pageFactory = $this->getMockBuilder(PageFactory::class)->disableOriginalConstructor()->getMock();
        $pageFactory->method('create')->willReturn($page);

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);

        $this->customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)->disableOriginalConstructor()->getMock();
        $this->customerSession->method('getCustomerDataObject')->willReturn($this->customer);

        $this->checkoutHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $result = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $result->method('setPath')->willReturn($result);

        $resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $resultFactory->method('create')->willReturn($result);

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getMessageManager')->willReturn($messageManager);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'pageFactory' => $pageFactory,
            'checkoutSession' => $this->checkoutSession,
            'customerSession' => $this->customerSession,
            'checkoutHelper' => $this->checkoutHelper,
            'paymentHelper' => $this->paymentHelper
        ]);
    }

    public function testExecuteRedirectCart()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteLoginNeeded()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(true);
        $this->quote->method('hasItems')->willReturn(true);
        $this->customer->method('getId')->willReturn(false);
        $this->quote->method('getCheckoutMethod')->willReturn(false);
        $this->checkoutHelper->method('isAllowedGuestCheckout')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecute()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(true);
        $this->quote->method('hasItems')->willReturn(true);
        $this->customer->method('getId')->willReturn(5);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Page::class, $result);
    }
}
