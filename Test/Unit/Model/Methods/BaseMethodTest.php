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

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use Payone\Core\Model\Methods\Paydirekt as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Model\Methods\Paydirekt;
use Payone\Core\Model\PayoneConfig;
use Magento\Payment\Model\Info;
use Payone\Core\Model\Api\Request\Authorization;
use Payone\Core\Model\Api\Request\Debit;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\Api\Request\Capture;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class BaseMethodTest extends BaseTestCase
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
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var Authorization|\PHPUnit_Framework_MockObject_MockObject
     */
    private $authorizationRequest;

    /**
     * @var Debit|\PHPUnit_Framework_MockObject_MockObject
     */
    private $debitRequest;

    /**
     * PAYONE capture request model
     *
     * @var Capture|\PHPUnit_Framework_MockObject_MockObject
     */
    private $captureRequest;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $this->authorizationRequest = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();
        $this->debitRequest = $this->getMockBuilder(Debit::class)->disableOriginalConstructor()->getMock();
        $this->captureRequest = $this->getMockBuilder(Capture::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'scopeConfig' => $this->scopeConfig,
            'authorizationRequest' => $this->authorizationRequest,
            'debitRequest' => $this->debitRequest,
            'captureRequest' => $this->captureRequest
        ]);
    }

    public function testGetInstructions()
    {
        $expected = 'instruction text';
        $this->scopeConfig->method('getValue')->willReturn($expected);
        $result = $this->classToTest->getInstructions();
        $this->assertEquals($expected, $result);
    }

    public function testGetConfigPaymentAction()
    {
        $result = $this->classToTest->getConfigPaymentAction();
        $expected = AbstractMethod::ACTION_AUTHORIZE;
        $this->assertEquals($expected, $result);
    }

    public function testCanUseForCountry()
    {
        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['allowspecific', 'global', 'payone_general', null, 0],
                ['specificcountry', 'global', 'payone_general', null, 'DE,AT']
            ]);
        $result = $this->classToTest->canUseForCountry('DE');
        $this->assertTrue($result);
    }

    public function testAuthorize()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);

        $aResponse = ['status' => 'REDIRECT', 'txid' => '12345', 'redirecturl' => 'http://testdomain.com'];
        $this->authorizationRequest->method('sendRequest')->willReturn($aResponse);

        $result = $this->classToTest->authorize($paymentInfo, 100);
        $this->assertInstanceOf(Paydirekt::class, $result);
    }

    public function testAuthorizeError()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);

        $aResponse = ['status' => 'ERROR', 'errorcode' => '42', 'customermessage' => 'Test error'];
        $this->authorizationRequest->method('sendRequest')->willReturn($aResponse);

        $this->expectException(LocalizedException::class);
        $this->classToTest->authorize($paymentInfo, 100);
    }

    public function testRefund()
    {
        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();

        $aResponse = ['status' => 'APPROVED'];
        $this->debitRequest->method('sendRequest')->willReturn($aResponse);

        $result = $this->classToTest->refund($paymentInfo, 100);
        $this->assertInstanceOf(Paydirekt::class, $result);
    }

    public function testRefundError()
    {
        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();

        $aResponse = ['status' => 'ERROR', 'errorcode' => '42', 'customermessage' => 'Test error'];
        $this->debitRequest->method('sendRequest')->willReturn($aResponse);

        $this->expectException(LocalizedException::class);
        $this->classToTest->refund($paymentInfo, 100);
    }

    public function testRefundNoResponse()
    {
        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();

        $this->debitRequest->method('sendRequest')->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->classToTest->refund($paymentInfo, 100);
    }

    public function testCapture()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn(true);

        $aResponse = ['status' => 'APPROVED'];
        $this->captureRequest->method('sendRequest')->willReturn($aResponse);

        $result = $this->classToTest->capture($paymentInfo, 100);
        $this->assertInstanceOf(Paydirekt::class, $result);
    }

    public function testCaptureError()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn(true);

        $aResponse = ['status' => 'ERROR', 'errorcode' => '42', 'customermessage' => 'Test error'];
        $this->captureRequest->method('sendRequest')->willReturn($aResponse);

        $this->expectException(LocalizedException::class);
        $this->classToTest->capture($paymentInfo, 100);
    }

    public function testCaptureNoResponse()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn(true);

        $this->captureRequest->method('sendRequest')->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->classToTest->capture($paymentInfo, 100);
    }

    public function testCaptureAuth()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn(false);

        $aResponse = ['status' => 'REDIRECT', 'txid' => '12345', 'redirecturl' => 'http://testdomain.com'];
        $this->authorizationRequest->method('sendRequest')->willReturn($aResponse);

        $result = $this->classToTest->capture($paymentInfo, 100);
        $this->assertInstanceOf(Paydirekt::class, $result);
    }

    public function testCanUseForCountryFalse()
    {
        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap(
                [
                    ['allowspecific', 'global', 'payone_general', null, 0],
                    ['specificcountry', 'global', 'payone_general', null, 'DE,AT'],
                    ['use_global', PayoneConfig::METHOD_PAYDIREKT, 'payone_payment', null, '0'],
                    ['allowspecific', PayoneConfig::METHOD_PAYDIREKT, 'payone_payment', null, '1'],
                    ['specificcountry', PayoneConfig::METHOD_PAYDIREKT, 'payone_payment', null, 'NL,AT'],
                ]
            );
        $result = $this->classToTest->canUseForCountry('DE');
        $this->assertFalse($result);
    }
}
