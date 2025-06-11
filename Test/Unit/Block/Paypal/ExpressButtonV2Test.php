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

namespace Payone\Core\Test\Unit\Block\Paypal;

use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Payone\Core\Block\Paypal\ExpressButtonV2 as ClassToTest;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Payment;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ExpressButtonV2Test extends BaseTestCase
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
     * @var (Payment&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $paymentHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('store4711code');

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getId')->willReturn('test4711');
        $quote->method('getStore')->willReturn($store);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        
        $apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $apiHelper->method('getCurrencyFromQuote')->willReturn('EUR');
        
        $methodInstance = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $methodInstance->method('getOperationMode')->willReturn('live');
        $methodInstance->method('getCustomConfigParam')->willReturn('test4711');
        $methodInstance->method('getAuthorizationMode')->willReturn(PayoneConfig::REQUEST_TYPE_AUTHORIZATION);

        $dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataHelper->method('getMethodInstance')->willReturn($methodInstance);
        
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $checkoutSession,
            'paymentHelper' => $this->paymentHelper,
            'apiHelper' => $apiHelper,
            'dataHelper' => $dataHelper,
        ]);
    }

    public function testGetMethodInstance()
    {
        $result = $this->classToTest->getMethodInstance();
        $this->assertInstanceOf(PayoneMethod::class, $result);
    }

    public function testGetQuoteId()
    {
        $expected = "test4711";
        $result = $this->classToTest->getQuoteId();
        $this->assertEquals($expected, $result);
    }

    public function testGetStoreCode()
    {
        $expected = "store4711code";
        $result = $this->classToTest->getStoreCode();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonColor()
    {
        $expected = "test4711";
        $result = $this->classToTest->getButtonColor();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonShape()
    {
        $expected = "test4711";
        $result = $this->classToTest->getButtonShape();
        $this->assertEquals($expected, $result);
    }

    public function testGetJavascriptUrl()
    {
        $this->paymentHelper->method('getConfigParam')->willReturn(true);

        $result = $this->classToTest->getJavascriptUrl();
        $result = strpos($result, "enable-funding") !== false;
        $this->assertTrue($result);
    }
}
