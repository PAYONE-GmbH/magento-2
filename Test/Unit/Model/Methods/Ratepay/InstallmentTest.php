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
 * @copyright 2003 - 2022 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Methods\Ratepay;

use Magento\Payment\Model\Info;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Ratepay;
use Payone\Core\Model\Methods\Ratepay\Installment as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Checkout\Model\Session;

class InstallmentTest extends BaseTestCase
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
     * @var Ratepay|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ratepayHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $shipping = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $shipping->method('getCountryId')->willReturn('DE');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAddress', 'getGrandTotal'])
            ->getMock();
        $quote->method('getShippingAddress')->willReturn($shipping);
        $quote->method('getGrandTotal')->willReturn(100);

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);
        
        $info = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();
        $info->method('getAdditionalInformation')->willReturn('1');

        $this->ratepayHelper = $this->getMockBuilder(Ratepay::class)->disableOriginalConstructor()->getMock();
        $this->ratepayHelper->method('getRatepayDeviceFingerprintToken')->willReturn('12345');

        $apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $apiHelper->method('getCurrencyFromOrder')->willReturn('EUR');
        $apiHelper->method('getQuoteAmount')->willReturn(123);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $checkoutSession,
            'ratepayHelper' => $this->ratepayHelper,
            'apiHelper' => $apiHelper,
        ]);
        $this->classToTest->setInfoInstance($info);
    }

    public function testGetSubTypeSpecificParameters()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getSubTypeSpecificParameters($order);
        $this->assertCount(8, $result);
    }

    public function testGetAllowedMonths()
    {
        $config = [
            'rate_min_normal' => '9',
            'month_allowed' => '3,6,9,a,12,15,18',
            'interestrate_default' => '9',
        ];

        $this->ratepayHelper->method('getShopConfigByQuote')->willReturn($config);

        $result = $this->classToTest->getAllowedMonths();
        $this->assertCount(3, $result);
    }

    public function testGetAllowedMonthsZero()
    {
        $config = [
            'rate_min_normal' => '9',
            'month_allowed' => '3,6,9,12,15,18',
            'interestrate_default' => '0',
        ];

        $this->ratepayHelper->method('getShopConfigByQuote')->willReturn($config);

        $result = $this->classToTest->getAllowedMonths();
        $this->assertCount(3, $result);
    }

    public function testGetAllowedMonthsNoShopId()
    {
        $this->ratepayHelper->method('getRatepayShopId')->willReturn(false);

        $result = $this->classToTest->getAllowedMonths();
        $this->assertEquals([], $result);
    }

    public function testGetAllowedMonthsNoShopConfig()
    {
        $this->ratepayHelper->method('getRatepayShopId')->willReturn("test");
        $this->ratepayHelper->method('getRatepayShopConfigById')->willReturn(false);

        $result = $this->classToTest->getAllowedMonths();
        $this->assertEquals([], $result);
    }
}
