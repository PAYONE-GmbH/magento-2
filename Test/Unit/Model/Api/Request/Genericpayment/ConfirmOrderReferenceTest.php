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

namespace Payone\Core\Test\Unit\Model\Api\Request\Genericpayment;

use Payone\Core\Model\Api\Request\Genericpayment\ConfirmOrderReference as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Methods\AmazonPay;
use Magento\Quote\Model\Quote;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Url;

class ConfirmOrderReferenceTest extends BaseTestCase
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

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper->method('getConfigParam')->willReturn('12345');
        
        $url = $this->getMockBuilder(Url::class)->disableOriginalConstructor()->getMock();
        $url->method('getUrl')->willReturn('https://www.payone.com/');

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'apiHelper' => $this->apiHelper,
            'url' => $url
        ]);
    }

    public function testSendRequest()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteCurrencyCode', 'getReservedOrderId', 'reserveOrderId', 'save'])
            ->getMock();
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->method('getReservedOrderId')->willReturn(false);
        $quote->method('reserveOrderId')->willReturn($quote);
        $quote->method('save')->willReturn($quote);
        $quote->method('getGrandTotal')->willReturn(100);

        $payment = $this->getMockBuilder(AmazonPay::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getClearingtype')->willReturn('fnc');
        $payment->method('formatReferenceNumber')->willReturn('123');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($payment, $quote, '123', '123');
        $this->assertEquals($response, $result);
    }

    public function testSendRequestException()
    {
        $exception = new \Exception('Error');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteCurrencyCode', 'getReservedOrderId', 'reserveOrderId', 'save'])
            ->getMock();
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->method('getReservedOrderId')->willReturn(false);
        $quote->method('reserveOrderId')->willReturn($quote);
        $quote->method('save')->willThrowException($exception);
        $quote->method('getGrandTotal')->willReturn(100);

        $payment = $this->getMockBuilder(AmazonPay::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getClearingtype')->willReturn('fnc');
        $payment->method('formatReferenceNumber')->willReturn('123');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($payment, $quote,'123', '123');
        $this->assertEquals($response, $result);
    }
}
