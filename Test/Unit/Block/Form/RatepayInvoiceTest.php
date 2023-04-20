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
 * PHP version 8
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2023 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Block\Form;

use Payone\Core\Block\Form\RatepayInvoice as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Ratepay;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Customer\Model\Data\Customer;

class RatepayInvoiceTest extends BaseTestCase
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
     * @var Ratepay
     */
    private $ratepayHelper;

    /**
     * @var Create
     */
    private $orderCreate;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->ratepayHelper = $this->getMockBuilder(Ratepay::class)->disableOriginalConstructor()->getMock();
        $this->orderCreate = $this->getMockBuilder(Create::class)->disableOriginalConstructor()->getMock();
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'ratepayHelper' => $this->ratepayHelper,
            'orderCreate' => $this->orderCreate,
        ]);
    }

    public function testGetQuote()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->orderCreate->method('getQuote')->willReturn($quote);
        
        $result = $this->classToTest->getQuote();
        $this->assertInstanceOf(Quote::class, $result);
    }

    public function testGetRatepayConfig()
    {
        $config = [
            'b2bAllowed' => '1',
            'differentAddressAllowed' => '1',
        ];
        $this->ratepayHelper->method('getRatepaySingleConfig')->willReturn($config);

        $result = $this->classToTest->getRatepayConfig();
        $this->assertEquals($config, $result);

        $result = $this->classToTest->isB2BModeAllowed();
        $this->assertTrue($result);

        $result = $this->classToTest->isDifferingDeliveryAddressAllowed();
        $this->assertTrue($result);
    }

    public function testGetRatepayConfigFalse()
    {
        $config = [];
        $this->ratepayHelper->method('getRatepaySingleConfig')->willReturn($config);

        $result = $this->classToTest->getRatepayConfig();
        $this->assertEquals($config, $result);

        $result = $this->classToTest->isB2BModeAllowed();
        $this->assertFalse($result);

        $result = $this->classToTest->isDifferingDeliveryAddressAllowed();
        $this->assertFalse($result);
    }

    public function testGetDevicefingerprintSnippetId()
    {
        $expected = "DeviceFingerprint";
        $this->ratepayHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getDevicefingerprintSnippetId();
        $this->assertEquals($expected, $result);
    }

    public function testGetDevicefingerprintToken()
    {
        $expected = "DeviceFingerprintToken";
        $this->ratepayHelper->method('getRatepayDeviceFingerprintToken')->willReturn($expected);

        $result = $this->classToTest->getDevicefingerprintToken();
        $this->assertEquals($expected, $result);
    }

    public function testIsB2BModeTrue()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCompany')->willReturn("Company");

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $this->orderCreate->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->isB2BMode();
        $this->assertTrue($result);

        $result = $this->classToTest->isBirthdayNeeded();
        $this->assertFalse($result);
    }

    public function testIsB2BModeFalse()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCompany')->willReturn(false);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $this->orderCreate->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->isB2BMode();
        $this->assertFalse($result);

        $result = $this->classToTest->isBirthdayNeeded();
        $this->assertTrue($result);
    }

    public function testHasDifferingDeliveryAddress()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getSameAsBilling')->willReturn(false);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getShippingAddress')->willReturn($address);

        $this->orderCreate->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->hasDifferingDeliveryAddress();
        $this->assertTrue($result);
    }

    public function testGetBirthday()
    {
        $expected = "19550505";

        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();
        $customer->method('getDob')->willReturn($expected);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getCustomer')->willReturn($customer);

        $this->orderCreate->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->getBirthday();
        $this->assertEquals($expected, $result);

        $result = $this->classToTest->getBirthdayPart("Y");
        $this->assertEquals("1955", $result);
    }

    public function testGetBirthdayPartEmpty()
    {
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();
        $customer->method('getDob')->willReturn(false);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getCustomer')->willReturn($customer);

        $this->orderCreate->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->getBirthdayPart("Y");
        $this->assertEquals('', $result);
    }

    public function testIsTelephoneNeeded()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getTelephone')->willReturn("0123456789");

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $this->orderCreate->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->isTelephoneNeeded();
        $this->assertFalse($result);
    }

    public function testIsTelephoneNeededFalse()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getTelephone')->willReturn(false);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $this->orderCreate->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->isTelephoneNeeded();
        $this->assertTrue($result);
    }
}
