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

namespace Payone\Core\Test\Unit\Model\Paypal;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Paypal\ReturnHandler as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress;
use Payone\Core\Helper\Order;
use Payone\Core\Helper\Checkout;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote\Payment;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Model\Methods\Paypal;
use Magento\Payment\Helper\Data;

class ReturnHandlerTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;


    private $payment;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmail', 'setEmail'])
            ->addMethods(['setShouldIgnoreValidation'])
            ->getMock();
        $address->method('getEmail')->willReturn('test@email.com');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId',
                'setIsActive',
                'save',
                'getBillingAddress',
                'setCustomerIsGuest',
                'getPayment',
                'setPayment',
                'collectTotals',
                'setBillingAddress',
                'getIsVirtual',
                'getShippingAddress',
                'setShippingAddress'
            ])
            ->addMethods([
                'setCustomerId',
                'setCustomerEmail',
                'setCustomerGroupId',
                'setInventoryProcessed',
            ])
            ->getMock();
        $quote->method('getId')->willReturn('12345');
        $quote->method('setIsActive')->willReturn($quote);
        $quote->method('getPayment')->willReturn($this->payment);
        $quote->method('collectTotals')->willReturn($quote);
        $quote->method('setCustomerId')->willReturn($quote);
        $quote->method('setCustomerEmail')->willReturn($quote);
        $quote->method('setCustomerIsGuest')->willReturn($quote);
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getIsVirtual')->willReturn(false);

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $response = [
            'add_paydata[shipping_firstname]' => 'Paul',
            'add_paydata[shipping_lastname]' => 'Payer',
            'add_paydata[shipping_street]' => 'Teststr. 12',
            'add_paydata[shipping_city]' => 'Testcity',
            'add_paydata[shipping_zip]' => '12345',
            'add_paydata[shipping_country]' => 'DE',
            'add_paydata[email]' => 'test@email.com'
        ];

        $genericRequest = $this->getMockBuilder(PayPalExpress::class)->disableOriginalConstructor()->getMock();
        $genericRequest->method('sendRequest')->willReturn($response);

        $orderHelper = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $orderHelper->method('fillSingleAddress')->willReturn($address);
        $orderHelper->method('updateAddresses')->willReturn($quote);

        $checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();
        $checkoutHelper->method('getCurrentCheckoutMethod')->willReturn(Onepage::METHOD_GUEST);

        $paymentMethod = $this->getMockBuilder(Paypal::class)->disableOriginalConstructor()->getMock();

        $dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataHelper->method('getMethodInstance')->willReturn($paymentMethod);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $checkoutSession,
            'genericRequest' => $genericRequest,
            'orderHelper' => $orderHelper,
            'checkoutHelper' => $checkoutHelper,
            'dataHelper' => $dataHelper,
        ]);
    }

    public function testHandlePayPalReturn()
    {
        $this->payment->method('getMethod')->willReturn(PayoneConfig::METHOD_PAYPAL);

        $result = $this->classToTest->handlePayPalReturn('12345');
        $this->assertNull($result);
    }

    public function testHandlePayPalReturnException()
    {
        $this->payment->method('getMethod')->willReturn(PayoneConfig::METHOD_CREDITCARD);

        $this->expectException(\Exception::class);
        $this->classToTest->handlePayPalReturn('12345');
    }
}
