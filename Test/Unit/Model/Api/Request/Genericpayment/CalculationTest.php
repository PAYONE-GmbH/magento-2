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

namespace Payone\Core\Test\Unit\Model\Api\Request\Genericpayment;

use Magento\Quote\Model\Quote;
use Payone\Core\Model\Api\Request\Genericpayment\Calculation as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Methods\Paypal;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class CalculationTest extends BaseTestCase
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

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'apiHelper' => $this->apiHelper
        ]);
    }

    public function testSendRequest()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');
        $address->method('getLastname')->willReturn('Payer');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress'])
            ->addMethods(['getQuoteCurrencyCode'])
            ->getMock();
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->method('getBillingAddress')->willReturn($address);

        $payment = $this->getMockBuilder(Paypal::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getClearingtype')->willReturn('fnc');
        $payment->method('getSubType')->willReturn('PYD');

        $this->shopHelper->method('getConfigParam')->willReturn('12345');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);
        $this->apiHelper->method('getQuoteAmount')->willReturn(100);

        $result = $this->classToTest->sendRequest($payment, $quote, 100);
        $this->assertEquals($response, $result);
    }

    public function testSendRequestRatepay()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuoteCurrencyCode'])
            ->getMock();
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');

        $payment = $this->getMockBuilder(Paypal::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getClearingtype')->willReturn('fnc');
        $payment->method('getSubType')->willReturn('PYD');

        $this->shopHelper->method('getConfigParam')->willReturn('12345');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);
        $this->apiHelper->method('getQuoteAmount')->willReturn(100);

        $result = $this->classToTest->sendRequestRatepay($payment, $quote, "test", "calculation-by-rate", 5);
        $this->assertEquals($response, $result);

        $result = $this->classToTest->sendRequestRatepay($payment, $quote, "test", "calculation-by-time", 5);
        $this->assertEquals($response, $result);
    }
}
