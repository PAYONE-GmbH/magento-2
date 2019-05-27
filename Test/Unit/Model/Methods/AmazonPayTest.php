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

namespace Payone\Core\Test\Unit\Model\Methods;

use Payone\Core\Model\Methods\AmazonPay as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Shop;
use Magento\Checkout\Model\Session;
use Magento\Payment\Model\Info;
use Payone\Core\Model\Api\Request\Authorization;
use Magento\Framework\Exception\LocalizedException;

class AmazonPayTest extends BaseTestCase
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

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAmazonWorkorderId',
                'getAmazonAddressToken',
                'getAmazonReferenceId',
                'getAmazonRetryAsync',
                'setAmazonRetryAsync',
                'unsPayoneRedirectUrl',
                'unsPayoneRedirectedPaymentMethod',
                'unsPayoneCanceledPaymentMethod',
                'unsPayoneIsError',
                'unsShowAmazonPendingNotice',
                'unsAmazonRetryAsync',
                'setShowAmazonPendingNotice',
                'setPayoneRedirectUrl',
                'setPayoneRedirectedPaymentMethod',
                'getPayoneCreatingSubstituteOrder',
            ])
            ->getMock();

        $this->checkoutSession->method('getAmazonWorkorderId')->willReturn('12345');
        $this->checkoutSession->method('getAmazonAddressToken')->willReturn('12345');
        $this->checkoutSession->method('getAmazonReferenceId')->willReturn('12345');

        $this->authorizationRequest = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'checkoutSession' => $this->checkoutSession,
            'authorizationRequest' => $this->authorizationRequest,
        ]);
    }

    public function testGetPaymentSpecificParametersSynchronousFirst()
    {
        $this->checkoutSession->method('getAmazonRetryAsync')->willReturn(true);
        $this->shopHelper->method('getConfigParam')->willReturn('synchronousFirst');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $this->assertCount(6, $result);
    }

    public function testGetPaymentSpecificParametersSynchronousThenAsync()
    {
        $this->checkoutSession->method('getAmazonRetryAsync')->willReturn(false);
        $this->shopHelper->method('getConfigParam')->willReturn('synchronousFirst');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $this->assertCount(6, $result);
    }

    public function testGetPaymentSpecificParametersAsynchronous()
    {
        $this->checkoutSession->method('getAmazonRetryAsync')->willReturn(true);
        $this->shopHelper->method('getConfigParam')->willReturn('asynchronous');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $this->assertCount(6, $result);
    }

    public function testGetPaymentSpecificParametersSynchronous()
    {
        $this->checkoutSession->method('getAmazonRetryAsync')->willReturn(true);
        $this->shopHelper->method('getConfigParam')->willReturn('synchronous');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $this->assertCount(7, $result);
    }

    public function testAuthorize()
    {
        $this->checkoutSession->method('getAmazonRetryAsync')->willReturn(true);
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);

        $aResponse = ['status' => 'ERROR', 'errorcode' => 980, 'customermessage' => 'test'];
        $this->authorizationRequest->method('sendRequest')->willReturn($aResponse);

        $this->expectException(LocalizedException::class);
        $this->classToTest->authorize($paymentInfo, 100);
    }

    public function testGetAuthorizationMode()
    {
        $expected = 'test';

        $this->shopHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getAuthorizationMode();
        $this->assertEquals($expected, $result);
    }
}
