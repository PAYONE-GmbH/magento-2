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

use Payone\Core\Block\Amazon\Button as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Base;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;

class ButtonTest extends BaseTestCase
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

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);

        $this->baseHelper = $this->getMockBuilder(Base::class)->disableOriginalConstructor()->getMock();
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'baseHelper' => $this->baseHelper
        ]);
    }

    public function testGetAlias()
    {
        $expected = 'payone.block.amazon.button';

        $result = $this->classToTest->getAlias();
        $this->assertEquals($expected, $result);
    }

    public function testGetClientId()
    {
        $expected = 'test';

        $this->baseHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getClientId();
        $this->assertEquals($expected, $result);
    }

    public function testGetSellerId()
    {
        $expected = 'test';

        $this->baseHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getSellerId();
        $this->assertEquals($expected, $result);
    }

    public function testGetButtonType()
    {
        $expected = 'test';

        $this->baseHelper->method('getConfigParam')->willReturn($expected);

        $result = $this->classToTest->getButtonType();
        $this->assertEquals($expected, $result);
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

    public function testGetRedirectUrl()
    {
        $expected = 'http://www.test.com';

        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getRedirectUrl();
        $this->assertEquals($expected, $result);
    }

    public function testGetCounterMiniBasket()
    {
        $this->classToTest->setData('payoneLayoutName', 'shortcutbuttons_0');

        $result = $this->classToTest->getCounter();
        $this->assertEquals(1, $result);
    }

    public function testGetCounterBasketPage()
    {
        $this->classToTest->setData('payoneLayoutName', 'checkout.cart.shortcut.buttons');

        $result = $this->classToTest->getCounter();
        $this->assertEquals(2, $result);
    }

    public function testGetCounterOther()
    {
        $this->classToTest->setData('payoneLayoutName', 'other');

        $result = $this->classToTest->getCounter();
        $this->assertEquals(3, $result);
    }

    public function testSetName()
    {
        $expected = "testName";

        $this->classToTest->setName($expected);
        $result = $this->classToTest->getName();

        $this->assertEquals($expected, $result);
    }
}
