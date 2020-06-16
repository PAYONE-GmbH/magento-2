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

namespace Payone\Core\Test\Unit\Controller\Paydirekt;

use Payone\Core\Controller\Paydirekt\Agreement as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\UrlInterface;
use Payone\Core\Model\Api\Request\PaydirektAgreement;
use Magento\Customer\Model\Customer;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Customer\Model\Address;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Data\Customer as CustomerData;
use Magento\Framework\Model\AbstractExtensibleModel;

class AgreementTest extends BaseTestCase
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
     * @var PaydirektAgreement|PayoneObjectManager
     */
    private $paydirektAgreement;

    /**
     * @var Customer|PayoneObjectManager
     */
    private $customer;

    /**
     * @var RequestInterface|PayoneObjectManager
     */
    private $request;

    /**
     * @var AbstractExtensibleModel|PayoneObjectManager
     */
    private $customerData;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();

        $response = $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock();
        $redirect = $this->getMockBuilder(RedirectInterface::class)->disableOriginalConstructor()->getMock();
        $urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $urlBuilder->method('getUrl')->willReturn('http://www.test.com');

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getUrl')->willReturn($urlBuilder);
        $context->method('getResponse')->willReturn($response);
        $context->method('getRedirect')->willReturn($redirect);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        
        $quoteAddress = $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCollectShippingRates', 'importCustomerAddressData'])
            ->getMock();
        $quoteAddress->method('setCollectShippingRates')->willReturn($quoteAddress);
        
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBillingAddress')->willReturn($quoteAddress);
        $quote->method('getShippingAddress')->willReturn($quoteAddress);
        $quote->method('collectTotals')->willReturn($quote);

        $checkoutSession = $this->getMockBuilder(CheckoutSession::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);
        
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getDataModel')->willReturn($addressData);

        $this->customerData = $this->getMockBuilder(CustomerData::class)->disableOriginalConstructor()->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPayonePaydirektRegistered',
                'setPayonePaydirektRegistered',
                'getDefaultBillingAddress',
                'getDefaultShippingAddress',
                'setPayoneGenericpaymentSubtotal',
                'getDataModel',
                'updateData',
                'save',
            ])
            ->getMock();
        $this->customer->method('getDefaultBillingAddress')->willReturn($address);
        $this->customer->method('getDefaultShippingAddress')->willReturn($address);
        $this->customer->method('getDataModel')->willReturn($this->customerData);

        $customerSession = $this->getMockBuilder(CustomerSession::class)->disableOriginalConstructor()->getMock();
        $customerSession->method('getCustomer')->willReturn($this->customer);

        $this->paydirektAgreement = $this->getMockBuilder(PaydirektAgreement::class)->disableOriginalConstructor()->getMock();
        
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $checkoutSession,
            'customerSession' => $customerSession,
            'paydirektAgreement' => $this->paydirektAgreement,
        ]);
    }

    public function testExecuteReview()
    {
        $this->customerData->method('getCustomAttribute')->willReturn(null);
        $this->request->method('getParam')->willReturn('123');

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteReviewRegistered()
    {
        $customAttribute = $this->getMockBuilder(AbstractExtensibleModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $customAttribute->method('getValue')->willReturn(true);

        $this->customerData->method('getCustomAttribute')->willReturn($customAttribute);
        $this->request->method('getParam')->willReturn('123');

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteRedirect()
    {
        $this->customerData->method('getCustomAttribute')->willReturn(null);
        $this->customer->method('getPayonePaydirektRegistered')->willReturn(false);

        $this->request->method('getParam')->willReturn(false);

        $response = [
            'status' => 'REDIRECT',
            'redirecturl' => 'test',
        ];
        $this->paydirektAgreement->method('sendAgreementRequest')->willReturn($response);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteError()
    {
        $this->customerData->method('getCustomAttribute')->willReturn(null);
        $this->customer->method('getPayonePaydirektRegistered')->willReturn(false);

        $this->request->method('getParam')->willReturn(false);

        $response = ['status' => 'ERROR'];
        $this->paydirektAgreement->method('sendAgreementRequest')->willReturn($response);

        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }
}
