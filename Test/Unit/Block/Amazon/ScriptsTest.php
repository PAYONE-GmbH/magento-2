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

namespace Payone\Core\Test\Unit\Block\Amazon;

use Payone\Core\Block\Amazon\Scripts as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Payment;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ScriptsTest extends BaseTestCase
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
     * @var Payment
     */
    private $paymentHelper;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $eventManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $context->method('getEventManager')->willReturn($eventManager);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'paymentHelper' => $this->paymentHelper
        ]);
    }

    public function testGetClientId()
    {
        $expected = 'test';

        $this->paymentHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getClientId();
        $this->assertEquals($expected, $result);
    }

    public function testGetSellerId()
    {
        $expected = 'test';

        $this->paymentHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getSellerId();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonType()
    {
        $expected = 'test';

        $this->paymentHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getButtonType();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonColor()
    {
        $expected = 'test';

        $this->paymentHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getButtonColor();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonLanguage()
    {
        $expected = 'test';

        $this->paymentHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getButtonLanguage();
        $this->assertEquals($expected, $result);
    }

    public function testGetRedirectUrl()
    {
        $expected = 'http://www.test.com';

        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getRedirectUrl();
        $this->assertEquals($expected, $result);
    }

    public function testGetWidgetUrl()
    {
        $expected = 'http://www.test.com';

        $this->paymentHelper->method('getAmazonPayWidgetUrl')->willReturn($expected);

        $result = $this->classToTest->getWidgetUrl();
        $this->assertEquals($expected, $result);
    }

    public function testToHtml()
    {
        $result = $this->classToTest->toHtml();
        error_log($result);
        $this->assertTrue(true);
    }

    public function testToHtmlEmpty()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(false);

        $result = $this->classToTest->toHtml();
        $this->assertEmpty($result);
    }
}
