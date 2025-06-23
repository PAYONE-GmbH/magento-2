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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Methods\Ratepay;

use Magento\Store\Model\Store;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Ratepay;
use Payone\Core\Helper\Shop;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Model\Methods\Ratepay\Invoice as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Info;
use Payone\Core\Model\PayoneConfig;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Payone\Core\Model\Api\Request\Genericpayment\PreCheck;
use Magento\Sales\Model\Order;
use Payone\Core\Model\Api\Request\Authorization;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\App\Config\ScopeConfigInterface;

class InvoiceTest extends BaseTestCase
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
     * @var PreCheck|\PHPUnit_Framework_MockObject_MockObject
     */
    private $precheckRequest;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Ratepay
     */
    private $ratepayHelper;

    /**
     * @var Authorization|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authorizationRequest;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $info = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();
        $info->method('getAdditionalInformation')->willReturn('1');

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('getAdditionalDataEntry')->willReturn('value');

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $shipping = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $shipping->method('getCountryId')->willReturn('DE');

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getStore')->willReturn($store);
        $quote->method('getShippingAddress')->willReturn($shipping);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods([
                'getPayonePaymentBans',
                'setPayonePaymentBans',
                'unsPayoneRedirectUrl',
                'unsPayoneRedirectedPaymentMethod',
                'unsPayoneCanceledPaymentMethod',
                'unsPayoneIsError',
                'unsShowAmazonPendingNotice',
                'unsAmazonRetryAsync',
                'unsPayoneRatepayDeviceFingerprintToken',
            ])
            ->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);
        $checkoutSession->method('getPayonePaymentBans')->willReturn(null);

        $this->precheckRequest = $this->getMockBuilder(PreCheck::class)->disableOriginalConstructor()->getMock();
        $this->authorizationRequest = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getConfigParam')->willReturn('display');

        $this->ratepayHelper = $this->getMockBuilder(Ratepay::class)->disableOriginalConstructor()->getMock();
        $this->ratepayHelper->method('getRatepayDeviceFingerprintToken')->willReturn('12345');

        $apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $apiHelper->method('getCurrencyFromOrder')->willReturn('EUR');
        $apiHelper->method('getQuoteAmount')->willReturn(123);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'toolkitHelper' => $toolkitHelper,
            'checkoutSession' => $checkoutSession,
            'precheckRequest' => $this->precheckRequest,
            'authorizationRequest' => $this->authorizationRequest,
            'shopHelper' => $shopHelper,
            'ratepayHelper' => $this->ratepayHelper,
            'apiHelper' => $apiHelper,
            'scopeConfig' => $this->scopeConfig
        ]);
        $this->classToTest->setInfoInstance($info);
    }

    public function testAssignData()
    {
        $data = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testGetPaymentSpecificParameters()
    {
        $shipping = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $shipping->method('getCountryId')->willReturn('DE');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getCustomerId')->willReturn('123');
        $order->method('getShippingAddress')->willReturn($shipping);

        $this->ratepayHelper->method('getRatepayShopId')->willReturn('54321');

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $expected = [
            'financingtype' => ClassToTest::METHOD_RATEPAY_SUBTYPE_INVOICE,
            'api_version' => '3.10',
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[device_token]' => '12345',
            'add_paydata[merchant_consumer_id]' => '123',
            'add_paydata[shop_id]' => '54321',
            'add_paydata[vat_id]' => '1',
            'birthday' => '1',
            'telephonenumber' => '1',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testAuthorizeError()
    {
        $this->authorizationRequest->method('sendRequest')->willReturn(['status' => 'ERROR', 'errorcode' => '307', 'customermessage' => 'ABC']);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getCustomerId')->willReturn('4711');

        $payment = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation'])
            ->addMethods(['getOrder'])
            ->getMock();
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getAdditionalInformation')->willReturn([]);

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $order->method('getPayment')->willReturn($payment);
        $order->method('getStore')->willReturn($store);

        $this->expectException(\Payone\Core\Model\Exception\AuthorizationException::class);
        $result = $this->classToTest->authorize($payment, 100);
    }

    public function testAuthorizeErrorGuest()
    {
        $this->authorizationRequest->method('sendRequest')->willReturn(['status' => 'ERROR', 'errorcode' => '307', 'customermessage' => 'ABC']);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getCustomerId')->willReturn(null);

        $payment = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation'])
            ->addMethods(['getOrder'])
            ->getMock();
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getAdditionalInformation')->willReturn([]);

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $order->method('getPayment')->willReturn($payment);
        $order->method('getStore')->willReturn($store);

        $this->expectException(\Payone\Core\Model\Exception\AuthorizationException::class);
        $result = $this->classToTest->authorize($payment, 100);
    }

    public function testAuthorize()
    {
        $this->authorizationRequest->method('sendRequest')->willReturn(['status' => 'APPROVED', 'txid' => '12345']);

        $response = ['status' => 'OK', 'workorderid' => 'WORKORDER'];
        $this->precheckRequest->method('sendRequest')->willReturn($response);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation'])
            ->addMethods(['getOrder'])
            ->getMock();
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getAdditionalInformation')->willReturn([]);

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $order->method('getPayment')->willReturn($payment);
        $order->method('getStore')->willReturn($store);

        $result = $this->classToTest->authorize($payment, 100);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testIsAvailableParent()
    {
        $this->scopeConfig->method('getValue')->willReturn(0);

        $result = $this->classToTest->isAvailable();
        $this->assertFalse($result);
    }

    public function testIsAvailableTrue()
    {
        $this->scopeConfig->method('getValue')->willReturn(1);

        $this->ratepayHelper->method('getRatepayShopId')->willReturn('54321');

        $result = $this->classToTest->isAvailable();
        $this->assertTrue($result);
    }

    public function testIsAvailableFalse()
    {
        $this->scopeConfig->method('getValue')->willReturn(1);

        $this->ratepayHelper->method('getShopIdByQuote')->willReturn(false);

        $result = $this->classToTest->isAvailable();
        $this->assertFalse($result);
    }

    public function testGetPaymentSpecificCaptureParameters()
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPayoneRatepayShopId'])
            ->getMock();
        $order->method('getPayoneRatepayShopId')->willReturn('12345');

        $result = $this->classToTest->getPaymentSpecificCaptureParameters($order);
        $this->assertArrayHasKey('add_paydata[shop_id]', $result);
    }

    public function testGetPaymentSpecificDebitParameters()
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPayoneRatepayShopId'])
            ->getMock();
        $order->method('getPayoneRatepayShopId')->willReturn('12345');

        $result = $this->classToTest->getPaymentSpecificDebitParameters($order);
        $this->assertArrayHasKey('add_paydata[shop_id]', $result);
    }
}
