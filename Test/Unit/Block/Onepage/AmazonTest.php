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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Block\Onepage;

use Magento\Quote\Model\Quote;
use Payone\Core\Block\Onepage\Amazon as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Payone\Core\Helper\Api;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Payment;
use Magento\Framework\App\RequestInterface;

class AmazonTest extends BaseTestCase
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
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Payment
     */
    private $paymentHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $context->method('getRequest')->willReturn($this->request);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods(['getPayoneMandate', 'getPayoneDebitError', 'unsPayoneDebitError', 'getAmazonReferenceId', 'getTriggerInvalidPayment'])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $apiHelper->method('getCurrencyFromQuote')->willReturn('EUR');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'paymentHelper' => $this->paymentHelper,
            'apiHelper' => $apiHelper
        ]);
    }

    public function testGetClientId()
    {
        $expected = '123';

        $this->paymentHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getClientId();
        $this->assertEquals($expected, $result);
    }

    public function testGetSellerId()
    {
        $expected = '123';

        $this->paymentHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getSellerId();
        $this->assertEquals($expected, $result);
    }

    public function testGetCurrency()
    {
        $result = $this->classToTest->getCurrency();
        $this->assertEquals('EUR', $result);
    }

    public function testGetCartUrl()
    {
        $expected = 'https://test.com';

        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getCartUrl();
        $this->assertEquals($expected, $result);
    }

    public function testGetLoadReviewUrl()
    {
        $expected = 'https://test.com';

        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getLoadReviewUrl();
        $this->assertEquals($expected, $result);
    }

    public function testGetErrorUrl()
    {
        $expected = 'https://test.com';

        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getErrorUrl();
        $this->assertEquals($expected, $result);
    }

    public function testGetWidgetUrl()
    {
        $expected = 'https://test.com';

        $this->paymentHelper->method('getAmazonPayWidgetUrl')->willReturn($expected);

        $result = $this->classToTest->getWidgetUrl();
        $this->assertEquals($expected, $result);
    }

    public function testIsInvalidPaymentTriggered()
    {
        $this->request->method('getParam')->willReturn(true);

        $result = $this->classToTest->isInvalidPaymentTriggered();
        $this->assertTrue($result);
    }

    public function testIsInvalidPaymentTriggeredFalse()
    {
        $this->request->method('getParam')->willReturn(false);

        $result = $this->classToTest->isInvalidPaymentTriggered();
        $this->assertFalse($result);
    }

    public function testGetOrderReferenceId()
    {
        $expected = '12345';
        $this->checkoutSession->method('getAmazonReferenceId')->willReturn($expected);

        $result = $this->classToTest->getOrderReferenceId();
        $this->assertEquals($expected, $result);
    }

    public function testTriggerInvalidPayment()
    {
        $this->checkoutSession->method('getTriggerInvalidPayment')->willReturn(null);

        $result = $this->classToTest->triggerInvalidPayment();
        $this->assertFalse($result);
    }

    public function testTriggerInvalidPaymentTrue()
    {
        $this->checkoutSession->method('getTriggerInvalidPayment')->willReturn(['a']);

        $result = $this->classToTest->triggerInvalidPayment();
        $this->assertTrue($result);
    }
}
