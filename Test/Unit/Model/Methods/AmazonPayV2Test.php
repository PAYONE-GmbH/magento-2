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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Methods;

use Magento\Store\Model\Store;
use Payone\Core\Model\Methods\AmazonPayV2 as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Shop;
use Magento\Checkout\Model\Session;
use Magento\Payment\Model\Info;
use Payone\Core\Model\Api\Request\Authorization;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Url;
use Magento\Framework\DataObject;
use Payone\Core\Helper\Toolkit;

class AmazonPayV2Test extends BaseTestCase
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
     * @var Shop
     */
    private $shopHelper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Authorization
     */
    private $authorizationRequest;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getStore')->willReturn($store);

        $info = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation'])
            ->addMethods(['getOrder'])
            ->getMock();
        $info->method('getAdditionalInformation')->willReturn('19010101');
        $info->method('getOrder')->willReturn($order);

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('getAdditionalDataEntry')->willReturn('12');

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'unsPayoneRedirectUrl',
                'unsPayoneRedirectedPaymentMethod',
                'unsPayoneCanceledPaymentMethod',
                'unsPayoneIsError',
                'unsShowAmazonPendingNotice',
                'unsAmazonRetryAsync',
                'getPayoneIsAmazonPayExpressPayment',
                'getPayoneWorkorderId',
                'setPayoneAmazonPaySignature',
                'setPayoneAmazonPayPayload',
                'setPayoneRedirectUrl',
                'setPayoneRedirectedPaymentMethod',
            ])
            ->getMock();

        $this->authorizationRequest = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $url = $this->getMockBuilder(Url::class)->disableOriginalConstructor()->getMock();
        $url->method('getUrl')->willReturn('payoneTest');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'toolkitHelper' => $toolkitHelper,
            'checkoutSession' => $this->checkoutSession,
            'authorizationRequest' => $this->authorizationRequest,
            'url' => $url,
        ]);
        $this->classToTest->setInfoInstance($info);
    }

    public function testIsAPBPayment()
    {
        $this->checkoutSession->method('getPayoneIsAmazonPayExpressPayment')->willReturn(false);

        $this->assertTrue($this->classToTest->isAPBPayment());
    }

    public function testIsAPBPaymentFalse()
    {
        $this->checkoutSession->method('getPayoneIsAmazonPayExpressPayment')->willReturn(true);

        $this->assertFalse($this->classToTest->isAPBPayment());
    }

    public function testGetMerchantId()
    {
        $expected = "testMerchant";

        $this->shopHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getMerchantId();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonColor()
    {
        $expected = "buttonColor";

        $this->shopHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getButtonColor();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonLanguage()
    {
        $this->shopHelper->method('getConfigParam')->willReturn('de-DE');

        $result = $this->classToTest->getButtonLanguage();
        $this->assertEquals('de_DE', $result);
    }

    public function testUseSandbox()
    {
        $this->shopHelper->method('getConfigParam')->willReturn('test');

        $result = $this->classToTest->useSandbox();
        $this->assertTrue($result);
    }

    public function testUseSandboxFalse()
    {
        $this->shopHelper->method('getConfigParam')->willReturn('live');

        $result = $this->classToTest->useSandbox();
        $this->assertFalse($result);
    }

    public function testGetSuccessUrl()
    {
        $result = $this->classToTest->getSuccessUrl();
        $this->assertStringContainsString("payone", $result);

        $this->classToTest->setNeedsReturnedUrl(true);

        $result = $this->classToTest->getSuccessUrl();
        $this->assertStringContainsString("payone", $result);
    }

    public function testAssignData()
    {
        $data = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testGetPaymentSpecificParameters()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getIsVirtual')->willReturn(false);

        $this->checkoutSession->method('getPayoneWorkorderId')->willReturn('12345');
        $this->checkoutSession->method('getPayoneIsAmazonPayExpressPayment')->willReturn(false);

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $this->assertCount(5, $result);
    }

    public function testGetFrontendConfig()
    {
        $this->shopHelper->method('getConfigParam')->willReturn('de-DE');

        $result = $this->classToTest->getFrontendConfig();
        $this->assertCount(5, $result);
    }

    public function testAuthorize()
    {
        $this->checkoutSession->method('getPayoneIsAmazonPayExpressPayment')->willReturn(false);

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getAdditionalInformation')->willReturn([]);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getStore')->willReturn($store);
        $order->method('getPayment')->willReturn($payment);

        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->addMethods(['getOrder'])->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);

        $aResponse = ['status' => 'REDIRECT', 'txid' => '12345', 'redirecturl' => 'http://testdomain.com', 'add_paydata[signature]' => 'test', 'add_paydata[payload]' => 'test'];
        $this->authorizationRequest->method('sendRequest')->willReturn($aResponse);

        $result = $this->classToTest->authorize($paymentInfo, 100);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }
}
