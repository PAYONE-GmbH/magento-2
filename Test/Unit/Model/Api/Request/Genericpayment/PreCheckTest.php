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
use Payone\Core\Model\Api\Request\Genericpayment\PreCheck as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Methods\Paypal;
use Magento\Quote\Model\Quote\Address;
use Magento\Payment\Model\Info;
use Payone\Core\Helper\Environment;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class PreCheckTest extends BaseTestCase
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

        $environmentHelper = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $environmentHelper->method('getRemoteIp')->willReturn('127.0.0.1');

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'apiHelper' => $this->apiHelper,
            'environmentHelper' => $environmentHelper
        ]);
    }

    public function testSendRequest()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('NL');
        $address->method('getFirstname')->willReturn('Paul');
        $address->method('getLastname')->willReturn('Payer');
        $address->method('getTelephone')->willReturn(false);
        $address->method('getCompany')->willReturn('Testcompany Ltd.');
        $address->method('getStreet')->willReturn(['Teststr. 5', '1st floor']);
        $address->method('getPostcode')->willReturn('12345');
        $address->method('getCity')->willReturn('Berlin');
        $address->method('getRegionCode')->willReturn('Berlin');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteCurrencyCode', 'getBillingAddress'])
            ->getMock();
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->method('getBillingAddress')->willReturn($address);

        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();
        $paymentInfo->method('getAdditionalInformation')->willReturn('value');

        $payment = $this->getMockBuilder(Paypal::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOperationMode', 'getClearingtype', 'getSubType', 'getLongSubType', 'getData', 'getInfoInstance', 'hasCustomConfig', 'getCustomConfigParam'])
            ->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getClearingtype')->willReturn('fnc');
        $payment->method('getSubType')->willReturn('PYD');
        $payment->method('getLongSubType')->willReturn('Payolution-Debit');
        $payment->method('getData')->willReturn(true);
        $payment->method('getInfoInstance')->willReturn($paymentInfo);
        $payment->method('hasCustomConfig')->willReturn(true);
        $payment->method('getCustomConfigParam')->willReturn(false);

        $this->shopHelper->method('getConfigParam')->willReturn('12345');
        $this->shopHelper->method('getLocale')->willReturn('de');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($payment, $quote, 100, 'birthday');
        $this->assertEquals($response, $result);
    }
}
