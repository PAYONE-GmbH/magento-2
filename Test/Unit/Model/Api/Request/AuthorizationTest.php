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

namespace Payone\Core\Test\Unit\Model\Api\Request;

use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;
use Payone\Core\Helper\Database;
use Payone\Core\Model\Api\Request\Authorization as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\PayoneConfig;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Customer\Api\Data\CustomerInterface;
use Payone\Core\Helper\Environment;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class AuthorizationTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apiHelper;

    /**
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getSequenceNumber')->willReturn('0');

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $this->apiHelper->method('isInvoiceDataNeeded')->willReturn(true);

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('handleSubstituteReplacement')->willReturn('test text');
        $toolkitHelper->method('getNarrativeText')->willReturn('narrative text');

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['aid', 'global', 'payone_general', null, '12345'],
                ['transmit_ip', 'global', 'payone_general', null, '1'],
                ['bill_as_del_address', PayoneConfig::METHOD_PAYPAL, 'payone_payment', null, true],
                ['ref_prefix', 'global', 'payone_general', null, 'test_']
            ]);

        $customer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();
        $customer->method('getGender')->willReturn('m');
        $customer->method('getDob')->willReturn('20000101');

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getCustomer')->willReturn($customer);

        $environmentHelper = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $environmentHelper->method('getRemoteIp')->willReturn('127.0.0.0.1');

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'databaseHelper' => $databaseHelper,
            'apiHelper' => $this->apiHelper,
            'toolkitHelper' => $toolkitHelper,
            'shopHelper' => $this->shopHelper,
            'checkoutSession' => $checkoutSession,
            'environmentHelper' => $environmentHelper
        ]);
    }

    /**
     * Get payment mock object
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentMock()
    {
        $payment = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $payment->method('getAuthorizationMode')->willReturn('authorization');
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('hasCustomConfig')->willReturn(true);
        $payment->method('formatReferenceNumber')->willReturn('12345');
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_PAYPAL);
        $payment->method('getClearingtype')->willReturn('wlt');
        $payment->method('getPaymentSpecificParameters')->willReturn([]);
        $payment->method('needsRedirectUrls')->willReturn(true);
        $payment->method('getSuccessUrl')->willReturn('http://testdomain.com');
        $payment->method('getErrorUrl')->willReturn('http://testdomain.com');
        $payment->method('getCancelUrl')->willReturn('http://testdomain.com');
        $payment->method('getCustomConfigParam')->willReturn('true');
        return $payment;
    }

    /**
     * Return address mock object
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getAddressMock()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('US');
        $address->method('getFirstname')->willReturn('Paul');
        $address->method('getLastname')->willReturn('Paytest');
        $address->method('getCompany')->willReturn('Testcompany Ltd.');
        $address->method('getStreet')->willReturn(['Teststr. 5', '1st floor']);
        $address->method('getPostcode')->willReturn('12345');
        $address->method('getCity')->willReturn('Berlin');
        $address->method('getRegionCode')->willReturn('CA');
        $address->method('getTelephone')->willReturn('0301234567');
        $address->method('getVatId')->willReturn('12345');
        return $address;
    }

    public function testSendRequest()
    {
        $payment = $this->getPaymentMock();
        $address = $this->getAddressMock();

        $expectedOrderId = '54321';

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRealOrderId', 'getOrderCurrencyCode', 'getCustomerId', 'getCustomerEmail', 'getBillingAddress', 'getShippingAddress'])
            ->getMock();
        $order->method('getRealOrderId')->willReturn($expectedOrderId);
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getCustomerId')->willReturn('12345');
        $order->method('getCustomerEmail')->willReturn('test@test.com');
        $order->method('getBillingAddress')->willReturn($address);
        $order->method('getShippingAddress')->willReturn($address);

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($payment, $order, 100);
        $this->assertEquals($response, $result);

        $orderId = $this->classToTest->getOrderId();
        $this->assertEquals($expectedOrderId, $orderId);
    }

    public function testSendRequestPaypal()
    {
        $payment = $this->getPaymentMock();
        $address = $this->getAddressMock();

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRealOrderId', 'getOrderCurrencyCode', 'getCustomerId', 'getCustomerEmail', 'getBillingAddress', 'getShippingAddress'])
            ->getMock();
        $order->method('getRealOrderId')->willReturn('54321');
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getCustomerId')->willReturn('12345');
        $order->method('getCustomerEmail')->willReturn('test@test.com');
        $order->method('getBillingAddress')->willReturn($address);
        $order->method('getShippingAddress')->willReturn(false);

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($payment, $order, 100);
        $this->assertEquals($response, $result);
    }
}
