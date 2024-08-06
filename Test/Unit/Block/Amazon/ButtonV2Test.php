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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Block\Amazon;

use Payone\Core\Block\Amazon\ButtonV2 as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Api;
use Payone\Core\Model\Methods\AmazonPayV2;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Base;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;

class ButtonV2Test extends BaseTestCase
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
     * @var Base
     */
    private $baseHelper;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSession;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $amazonPayment = $this->getMockBuilder(AmazonPayV2::class)->disableOriginalConstructor()->getMock();
        $amazonPayment->method('getMerchantId')->willReturn('test');
        $amazonPayment->method('useSandbox')->willReturn(false);
        $amazonPayment->method('getButtonColor')->willReturn('test');
        $amazonPayment->method('getButtonLanguage')->willReturn('test');

        $apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $apiHelper->method('getCurrencyFromQuote')->willReturn('EUR');
        $apiHelper->method('getQuoteAmount')->willReturn('1000');

        $this->baseHelper = $this->getMockBuilder(Base::class)->disableOriginalConstructor()->getMock();
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'baseHelper' => $this->baseHelper,
            'checkoutSession' => $this->checkoutSession,
            'amazonPayment' => $amazonPayment,
            'apiHelper' => $apiHelper,
        ]);
    }

    public function testGetAlias()
    {
        $expected = 'payone.block.amazon.buttonv2';

        $result = $this->classToTest->getAlias();
        $this->assertEquals($expected, $result);
    }

    public function testSetName()
    {
        $expected = "testName";

        $this->classToTest->setName($expected);
        $result = $this->classToTest->getName();

        $this->assertEquals($expected, $result);
    }

    public function testGetQuoteId()
    {
        $expected = 'test';

        $oQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $oQuote->method('getId')->willReturn($expected);

        $this->checkoutSession->method('getQuote')->willReturn($oQuote);

        $result = $this->classToTest->getQuoteId();

        $this->assertEquals($expected, $result);
    }

    public function testGetStoreCode()
    {
        $expected = 'test';

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn($expected);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getStore')->willReturn($store);

        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->getStoreCode();

        $this->assertEquals($expected, $result);
    }

    public function testGetButtonId()
    {
        $this->classToTest->setName('fallback');
        $result = $this->classToTest->getButtonId();
        $this->assertEquals('AmazonPayButton', $result);

        $this->classToTest->setName('checkout.cart.shortcut.buttons');
        $result = $this->classToTest->getButtonId();
        $this->assertEquals('AmazonPayButtonBasket', $result);

        $this->classToTest->setName('shortcutbuttons');
        $result = $this->classToTest->getButtonId();
        $this->assertEquals('AmazonPayButtonMiniBasket', $result);
    }

    public function testGetQuote()
    {
        $oQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $this->checkoutSession->method('getQuote')->willReturn($oQuote);

        $result = $this->classToTest->getQuote();

        $this->assertInstanceOf(Quote::class, $result);
    }

    public function testGetPublicKeyId()
    {
        $result = $this->classToTest->getPublicKeyId();
        $this->assertEquals(AmazonPayV2::BUTTON_PUBLIC_KEY, $result);
    }

    public function testGetMerchantId()
    {
        $result = $this->classToTest->getMerchantId();
        $this->assertEquals('test', $result);
    }

    public function testIsTestMode()
    {
        $result = $this->classToTest->isTestMode();
        $this->assertFalse($result);
    }

    public function testGetCurrency()
    {
        $oQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $this->checkoutSession->method('getQuote')->willReturn($oQuote);

        $result = $this->classToTest->getCurrency();
        $this->assertEquals('EUR', $result);
    }

    public function testGetAmount()
    {
        $oQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $this->checkoutSession->method('getQuote')->willReturn($oQuote);

        $result = $this->classToTest->getAmount();
        $this->assertEquals('1000', $result);
    }

    public function testGetProductType()
    {
        $oQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $oQuote->method('isVirtual')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($oQuote);

        $result = $this->classToTest->getProductType();
        $this->assertEquals("PayAndShip", $result);
    }

    public function testGetProductTypeVirtual()
    {
        $oQuote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $oQuote->method('isVirtual')->willReturn(true);
        $this->checkoutSession->method('getQuote')->willReturn($oQuote);

        $result = $this->classToTest->getProductType();
        $this->assertEquals("PayOnly", $result);
    }

    public function testGetPlacement()
    {
        $result = $this->classToTest->getPlacement();
        $this->assertEquals("Home", $result);

        $this->classToTest->setName("checkout.cart.shortcut.buttons");
        $result = $this->classToTest->getPlacement();
        $this->assertEquals("Cart", $result);
    }

    public function testGetButtonColor()
    {
        $expected = 'test';

        $this->baseHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getButtonColor();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonLanguage()
    {
        $expected = 'test';

        $this->baseHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getButtonLanguage();
        $this->assertEquals($expected, $result);
    }
}
