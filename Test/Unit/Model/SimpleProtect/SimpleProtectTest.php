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

namespace Payone\Core\Test\Unit\Model\SimpleProtect;

use Payone\Core\Model\SimpleProtect\SimpleProtect as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Payment\Model\MethodInterface;
use Payone\Core\Model\PayoneConfig;
use Magento\Quote\Api\Data\AddressInterface;

class SimpleProtectTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();
        $this->classToTest = $objectManager->getObject(ClassToTest::class);
    }

    public function testHandlePrePaymentSelection()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_DEBIT);
        $paymentMethods = [$payment];

        $result = $this->classToTest->handlePrePaymentSelection($quote, $paymentMethods);
        $this->assertEquals($paymentMethods, $result);
    }

    public function testHandlePostPaymentSelection()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $this->classToTest->handlePostPaymentSelection($quote);
        $this->assertNull(null);
    }

    public function testHandleEnterOrChangeBillingAddress()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->handleEnterOrChangeBillingAddress($address, false, 100);
        $this->assertTrue($result);
    }

    public function testHandleEnterOrChangeShippingAddress()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->handleEnterOrChangeShippingAddress($address, false, 100);
        $this->assertTrue($result);
    }

    public function testHandlePreCheckout()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->handlePreCheckout($quote);
        $this->assertCount(2, $result);
    }

    public function testIsAddresscheckBillingEnabled()
    {
        $result = $this->classToTest->isAddresscheckBillingEnabled();
        $this->assertFalse($result);
    }

    public function testIsAddresscheckShippingEnabled()
    {
        $result = $this->classToTest->isAddresscheckShippingEnabled();
        $this->assertFalse($result);
    }

    public function testIsAddresscheckCorrectionConfirmationNeeded()
    {
        $result = $this->classToTest->isAddresscheckCorrectionConfirmationNeeded();
        $this->assertTrue($result);
    }
}
