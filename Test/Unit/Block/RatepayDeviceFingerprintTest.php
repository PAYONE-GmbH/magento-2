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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Block;

use Payone\Core\Block\RatepayDeviceFingerprint as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Payment;
use Payone\Core\Helper\Ratepay;
use Payone\Core\Helper\Payment as PaymentHelper;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class RatepayDeviceFingerprintTest extends BaseTestCase
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
     * @var Ratepay|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ratepayHelper;

    /**
     * @var PaymentHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->ratepayHelper = $this->getMockBuilder(Ratepay::class)->disableOriginalConstructor()->getMock();
        $this->paymentHelper = $this->getMockBuilder(PaymentHelper::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'ratepayHelper' => $this->ratepayHelper,
            'paymentHelper' => $this->paymentHelper,
        ]);
    }

    public function testGetDevicefingerprintSnippetId()
    {
        $expected = "ratepay";
        $this->ratepayHelper->method("getConfigParam")->willReturn($expected);

        $result = $this->classToTest->getDevicefingerprintSnippetId();
        $this->assertEquals($expected, $result);
    }

    public function testGetDevicefingerprintToken()
    {
        $expected = "12345";
        $this->ratepayHelper->method("getRatepayDeviceFingerprintToken")->willReturn($expected);

        $result = $this->classToTest->getDevicefingerprintToken();
        $this->assertEquals($expected, $result);
    }

    public function testToHtml()
    {
        $result = $this->classToTest->toHtml();
        $this->assertTrue(true);
    }

    public function testToHtmlEmpty()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(false);

        $result = $this->classToTest->toHtml();
        $this->assertEmpty($result);
    }
}
