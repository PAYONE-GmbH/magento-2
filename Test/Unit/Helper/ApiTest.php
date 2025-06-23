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

namespace Payone\Core\Test\Unit\Helper;

use Magento\Payment\Model\Info;
use Magento\Quote\Model\Quote;
use Payone\Core\Helper\Api;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Payone\Core\Helper\Connection\CurlPhp;
use Payone\Core\Helper\Connection\CurlCli;
use Payone\Core\Helper\Connection\Fsockopen;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ApiTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var PayoneMethod
     */
    private $payment;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var CurlPhp|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connCurlPhp;

    /**
     * @var CurlCli|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connCurlCli;

    /**
     * @var Fsockopen|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connFsockopen;

    /**
     * @var StoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $this->store->method('getCode')->willReturn('test');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getStore')->willReturn($this->store);
        
        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrder'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);

        $this->payment = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getInfoInstance')->willReturn($paymentInfo);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($this->store);

        $this->connCurlPhp = $this->getMockBuilder(CurlPhp::class)->disableOriginalConstructor()->getMock();
        $this->connCurlCli = $this->getMockBuilder(CurlCli::class)->disableOriginalConstructor()->getMock();
        $this->connFsockopen = $this->getMockBuilder(Fsockopen::class)->disableOriginalConstructor()->getMock();

        $this->api = $this->objectManager->getObject(Api::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'connCurlPhp' => $this->connCurlPhp,
            'connCurlCli' => $this->connCurlCli,
            'connFsockopen' => $this->connFsockopen
        ]);
    }

    public function testIsInvoiceDataNeededForRequest()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/global/request_type', ScopeInterface::SCOPE_STORES, null, 'authorization'],
                    ['payone_general/invoicing/transmit_enabled', ScopeInterface::SCOPE_STORES, null, 1]
                ]
            );
        $this->payment->method('needsProductInfo')->willReturn(true);

        $result = $this->api->isInvoiceDataNeeded($this->payment);
        $this->assertTrue($result);
    }

    public function testIsInvoiceDataNotNeededForRequest()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/global/request_type', ScopeInterface::SCOPE_STORES, null, 'preauthorization'],
                    ['payone_general/invoicing/transmit_enabled', ScopeInterface::SCOPE_STORES, null, 0]
                ]
            );
        $this->payment->method('needsProductInfo')->willReturn(false);

        $result = $this->api->isInvoiceDataNeeded($this->payment);
        $this->assertFalse($result);
    }

    public function testIfOrderDataIsSetCorrectly()
    {
        $oOrder = $this->objectManager->getObject(Order::class);

        $reference = 'ref123';
        $request = 'authorization';
        $mode = 'live';
        $mandate = 'mandate';
        $txid = '12345';
        $aRequest = [
            'reference' => $reference,
            'request' => $request,
            'mode' => $mode,
            'mandate_identification' => $mandate,
            'add_paydata[installment_duration]' => '5'
        ];
        $aResponse = [
            'txid' => $txid,
            'clearing_reference' => 'REFERENCE',
            'add_paydata[clearing_reference]' => 'REFERENCE',
            'add_paydata[workorderid]' => 'WORKORDER'
        ];

        $this->api->addPayoneOrderData($oOrder, $aRequest, $aResponse);
        $this->assertEquals($reference, $oOrder->getPayoneRefnr());
        $this->assertEquals($request, $oOrder->getPayoneAuthmode());
        $this->assertEquals($mode, $oOrder->getPayoneMode());
        $this->assertEquals($mandate, $oOrder->getPayoneMandateId());
        $this->assertEquals($txid, $oOrder->getPayoneTxid());
    }

    public function testIfOrderDataIsSetCorrectlyResponseMandate()
    {
        $oOrder = $this->objectManager->getObject(Order::class);

        $mandate = 'mandate';
        $aRequest = [
            'reference' => 'ref123',
            'request' => 'authorization',
            'mode' => 'live',
            'workorderid' => 'WORKORDER',
        ];
        $aResponse = ['mandate_identification' => $mandate];

        $this->api->addPayoneOrderData($oOrder, $aRequest, $aResponse);
        $this->assertEquals($mandate, $oOrder->getPayoneMandateId());
    }

    public function testIfTheRequestUrlIsGeneratedCorretly()
    {
        $aParameters = [
            'sku' => ['sku5', 'sku3'],
            'session' => 'test-session',
            'random' => 'sp ace'
        ];
        $sApiUrl = 'http://test.com';
        $result = $this->api->getRequestUrl($aParameters, $sApiUrl);
        $expected = 'http://test.com?sku[0]=sku5&sku[1]=sku3&session=test-session&random=sp+ace';
        $this->assertEquals($result, $expected);
    }

    public function testIfParseErrorIsReturnedCorrectly()
    {
        $return = $this->api->sendApiRequest("http://user@:80");
        $expected = ["errormessage" => "Payone API request URL could not be parsed."];
        $this->assertEquals($expected, $return);
    }

    public function testSendApiRequestReturnValueCurlPhp()
    {
        $this->connCurlPhp->method('isApplicable')->willReturn(true);

        $sendOutput = [
            'status=APPROVED',
            'txid=42',
            'userid=0815',
            'test',
            ''
        ];
        $this->connCurlPhp->method('sendCurlPhpRequest')->willReturn($sendOutput);

        $return = $this->api->sendApiRequest("http://payone.de");
        $expected = [
            'status' => 'APPROVED',
            'txid' => '42',
            'userid' => '0815',
            3 => 'test'
        ];
        $this->assertEquals($expected, $return);
    }

    public function testSendApiRequestReturnValueCurlPhpError()
    {
        $this->connCurlPhp->method('isApplicable')->willReturn(true);

        $sendOutput = [];
        $this->connCurlPhp->method('sendCurlPhpRequest')->willReturn($sendOutput);

        $return = $this->api->sendApiRequest("http://payone.de");
        $expected = [
            'status' => 'ERROR',
            'errorcode' => '0',
            'customermessage' => 'No connection to external service provider possible (timeout)',
        ];
        $this->assertEquals($expected, $return);
    }

    public function testSendApiRequestReturnValueCurlCli()
    {
        $this->connCurlPhp->method('isApplicable')->willReturn(false);
        $this->connCurlCli->method('isApplicable')->willReturn(true);

        $sendOutput = [
            'status=APPROVED',
            'txid=42',
            'userid=0815',
            'test',
            ''
        ];
        $this->connCurlCli->method('sendCurlCliRequest')->willReturn($sendOutput);

        $return = $this->api->sendApiRequest("http://payone.de");
        $expected = [
            'status' => 'APPROVED',
            'txid' => '42',
            'userid' => '0815',
            3 => 'test'
        ];
        $this->assertEquals($expected, $return);
    }

    public function testSendApiRequestReturnValueFsockopen()
    {
        $this->connCurlPhp->method('isApplicable')->willReturn(false);
        $this->connCurlCli->method('isApplicable')->willReturn(false);

        $sendOutput = [
            'status=APPROVED',
            'txid=42',
            'userid=0815',
            'test',
            ''
        ];
        $this->connFsockopen->method('sendSocketRequest')->willReturn($sendOutput);

        $return = $this->api->sendApiRequest("http://payone.de");
        $expected = [
            'status' => 'APPROVED',
            'txid' => '42',
            'userid' => '0815',
            3 => 'test'
        ];
        $this->assertEquals($expected, $return);
    }

    public function testGetCurrencyFromOrderBase()
    {
        $expected = 'EUR';

        $oOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $oOrder->method('getBaseCurrencyCode')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getCurrencyFromOrder($oOrder);
        $this->assertEquals($expected, $result);
    }

    public function testGetCurrencyFromOrderDisplay()
    {
        $this->scopeConfig->method('getValue')->willReturn('display');
        $expected = 'EUR';

        $oOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $oOrder->method('getBaseCurrencyCode')->willReturn('USD');
        $oOrder->method('getOrderCurrencyCode')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getCurrencyFromOrder($oOrder);
        $this->assertEquals($expected, $result);
    }

    public function testGetOrderAmountBase()
    {
        $expected = '100';

        $oOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseGrandTotal', 'getStore'])
            ->getMock();
        $oOrder->method('getBaseGrandTotal')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getOrderAmount($oOrder);
        $this->assertEquals($expected, $result);
    }

    public function testGetOrderAmountDisplay()
    {
        $this->scopeConfig->method('getValue')->willReturn('display');
        $expected = '100';

        $oOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseGrandTotal', 'getGrandTotal', 'getStore'])
            ->getMock();
        $oOrder->method('getBaseGrandTotal')->willReturn(200);
        $oOrder->method('getGrandTotal')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getOrderAmount($oOrder);
        $this->assertEquals($expected, $result);
    }

    public function testGetCurrencyFromQuoteBase()
    {
        $expected = 'EUR';

        $oOrder = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->addMethods(['getBaseCurrencyCode'])
            ->getMock();
        $oOrder->method('getBaseCurrencyCode')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getCurrencyFromQuote($oOrder);
        $this->assertEquals($expected, $result);
    }

    public function testGetCurrencyFromQuoteDisplay()
    {
        $this->scopeConfig->method('getValue')->willReturn('display');
        $expected = 'EUR';

        $oOrder = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->addMethods(['getBaseCurrencyCode', 'getQuoteCurrencyCode'])
            ->getMock();
        $oOrder->method('getBaseCurrencyCode')->willReturn($expected);
        $oOrder->method('getQuoteCurrencyCode')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getCurrencyFromQuote($oOrder);
        $this->assertEquals($expected, $result);
    }

    public function testGetQuoteAmountBase()
    {
        $expected = '100';

        $oOrder = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->addMethods(['getBaseGrandTotal'])
            ->getMock();
        $oOrder->method('getBaseGrandTotal')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getQuoteAmount($oOrder);
        $this->assertEquals($expected, $result);
    }

    public function testGetQuoteAmountDisplay()
    {
        $this->scopeConfig->method('getValue')->willReturn('display');
        $expected = '100';

        $oOrder = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->addMethods(['getBaseGrandTotal', 'getGrandTotal'])
            ->getMock();
        $oOrder->method('getBaseGrandTotal')->willReturn(200);
        $oOrder->method('getGrandTotal')->willReturn($expected);
        $oOrder->method('getStore')->willReturn($this->store);

        $result = $this->api->getQuoteAmount($oOrder);
        $this->assertEquals($expected, $result);
    }

}
