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

namespace Payone\Core\Test\Unit\Block;

use Payone\Core\Block\RedirectReturn as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Payment;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class RedirectReturnTest extends BaseTestCase
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
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsPayoneRedirectCancellation', 'unsIsPayoneRedirectCancellation', 'getQuote'])
            ->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $this->checkoutSession
        ]);
    }

    public function testIsRedirectCancellation()
    {
        $this->checkoutSession->method('getIsPayoneRedirectCancellation')->willReturn(true);

        $result = $this->classToTest->isRedirectCancellation();
        $this->assertTrue($result);
    }

    public function testIsNotRedirectCancellation()
    {
        $this->checkoutSession->method('getIsPayoneRedirectCancellation')->willReturn(false);

        $result = $this->classToTest->isRedirectCancellation();
        $this->assertFalse($result);
    }

    public function testGetQuote()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->getQuote();
        $this->assertInstanceOf(Quote::class, $result);
    }

    public function testGetShippingAddress()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getShippingAddress')->willReturn($address);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->getShippingAddress();
        $this->assertInstanceOf(Address::class, $result);
    }

    public function testGetShippingAddressFalse()
    {
        $this->checkoutSession->method('getQuote')->willReturn(false);

        $result = $this->classToTest->getShippingAddress();
        $this->assertFalse($result);
    }

    public function testGetQuotePayment()
    {
        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->getQuotePayment();
        $this->assertInstanceOf(Payment::class, $result);
    }

    public function testGetQuotePaymentFalse()
    {
        $this->checkoutSession->method('getQuote')->willReturn(false);

        $result = $this->classToTest->getQuotePayment();
        $this->assertFalse($result);
    }

    public function testIsGuest()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getCustomerIsGuest')->willReturn(true);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->isGuest();
        $this->assertTrue($result);
    }
}
