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

namespace Payone\Core\Test\Unit\Block\Onepage;

use Magento\Checkout\Model\Session;
use Payone\Core\Block\Onepage\Review as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Address\Config;
use Magento\Framework\DataObject;
use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Tax\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Escaper;
use Magento\Quote\Model\Quote\Payment;
use Magento\Payment\Model\MethodInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Event\ManagerInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ReviewTest extends BaseTestCase
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
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $taxHelper;

    /**
     * @var PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceCurrency;

    /**
     * @var Store|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $escaper = $this->getMockBuilder(Escaper::class)->disableOriginalConstructor()->getMock();
        $escaper->method('escapeHtml')->willReturn('html');

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $eventManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);
        $context->method('getEscaper')->willReturn($escaper);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $context->method('getEventManager')->willReturn($eventManager);

        $this->store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->quote->method('getStore')->willReturn($this->store);

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($this->quote);

        $renderer = $this->getMockBuilder(RendererInterface::class)->disableOriginalConstructor()->getMock();
        $renderer->method('renderArray')->willReturn('address');

        $object = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getRenderer'])->getMock();
        $object->method('getRenderer')->willReturn($renderer);

        $addressConfig = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $addressConfig->method('getFormatByCode')->willReturn($object);

        $this->taxHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $this->taxHelper->method('displayShippingPriceIncludingTax')->willReturn(false);
        $this->taxHelper->method('displayShippingBothPrices')->willReturn(true);

        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $checkoutSession,
            'addressConfig' => $addressConfig,
            'taxHelper' => $this->taxHelper,
            'priceCurrency' => $this->priceCurrency
        ]);
    }

    public function testGetShippingAddress()
    {
        $expected = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $this->quote->method('getShippingAddress')->willReturn($expected);
        $this->quote->method('getIsVirtual')->willReturn(false);
        $result = $this->classToTest->getShippingAddress();
        $this->assertEquals($expected, $result);
    }

    public function testGetShippingAddressVirtual()
    {
        $this->quote->method('getIsVirtual')->willReturn(true);
        $result = $this->classToTest->getShippingAddress();
        $this->assertFalse($result);
    }

    public function testRenderAddress()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getData')->willReturn(['key' => 'value']);
        $result = $this->classToTest->renderAddress($address);
        $expected = 'address'; // see setUp method
        $this->assertEquals($expected, $result);
    }

    public function testGetCarrierName()
    {
        $expected = 'carrier';
        $this->scopeConfig->method('getValue')->willReturn($expected);
        $result = $this->classToTest->getCarrierName('test');
        $this->assertEquals($expected, $result);
    }

    public function testGetCarrierNameNoCarrier()
    {
        $expected = 'test';
        $this->scopeConfig->method('getValue')->willReturn(false);
        $result = $this->classToTest->getCarrierName($expected);
        $this->assertEquals($expected, $result);
    }

    public function testRenderShippingRateValue()
    {
        $object = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getErrorMessage'])->getMock();
        $object->method('getErrorMessage')->willReturn('error');
        $result = $this->classToTest->renderShippingRateValue($object);
        $this->assertEquals('', $result);
    }

    public function testRenderShippingRateValueNoError()
    {
        $expected = 'code';
        $object = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getErrorMessage', 'getCode'])->getMock();
        $object->method('getErrorMessage')->willReturn(false);
        $object->method('getCode')->willReturn($expected);
        $result = $this->classToTest->renderShippingRateValue($object);
        $this->assertEquals($expected, $result);
    }

    public function testRenderShippingRateOption()
    {
        $object = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getErrorMessage', 'getMethodTitle', 'getPrice'])
            ->getMock();
        $object->method('getErrorMessage')->willReturn(false);
        $object->method('getMethodTitle')->willReturn('Free Shipping');
        $object->method('getPrice')->willReturn(5);

        $this->taxHelper->expects($this->any())
            ->method('getShippingPrice')
            ->willReturnMap([[5, false, null, null, null, 5], [5, true, null, null, null, 6]]);
        $this->priceCurrency->expects($this->any())
            ->method('convertAndFormat')
            ->willReturnMap([
                [5, true, PriceCurrencyInterface::DEFAULT_PRECISION, $this->store, null, 5],
                [6, true, PriceCurrencyInterface::DEFAULT_PRECISION, $this->store, null, 6]
            ]);

        $result = $this->classToTest->renderShippingRateOption($object);
        $expected = 'html - 5 (html 6)';
        $this->assertEquals($expected, $result);
    }

    public function testRenderShippingRateOptionError()
    {
        $object = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getErrorMessage', 'getMethodTitle'])->getMock();
        $object->method('getErrorMessage')->willReturn('error');
        $object->method('getMethodTitle')->willReturn('Free Shipping');
        $result = $this->classToTest->renderShippingRateOption($object);
        $expected = 'html - error';
        $this->assertEquals($expected, $result);
    }

    public function testGetCurrentShippingRate()
    {
        $result = $this->classToTest->getCurrentShippingRate();
        $this->assertNull($result);
    }

    public function testGetEmail()
    {
        $expected = 'test@test.de';
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getEmail')->willReturn($expected);
        $this->quote->method('getBillingAddress')->willReturn($address);
        $result = $this->classToTest->getEmail();
        $this->assertEquals($expected, $result);
    }

    public function testToHtml()
    {
        $infoInstance = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $infoInstance->method('getTitle')->willReturn('Payone');

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($infoInstance);

        $rate = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getCode'])->getMock();
        $rate->method('getCode')->willReturn('free');

        $rate2 = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getCode'])->getMock();
        $rate2->method('getCode')->willReturn('not_free');

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getGroupedAllShippingRates')->willReturn([[$rate2, $rate]]);
        $address->method('getShippingMethod')->willReturn('free');

        $this->quote->method('getPayment')->willReturn($payment);
        $this->quote->method('getIsVirtual')->willReturn(false);
        $this->quote->method('getShippingAddress')->willReturn($address);

        $result = $this->classToTest->toHtml();
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    public function testToHtmlVirtual()
    {
        $infoInstance = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $infoInstance->method('getTitle')->willReturn('Payone');

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($infoInstance);

        $this->quote->method('getPayment')->willReturn($payment);
        $this->quote->method('getIsVirtual')->willReturn(true);

        $result = $this->classToTest->toHtml();
        $expected = '';
        $this->assertEquals($expected, $result);
    }
}
