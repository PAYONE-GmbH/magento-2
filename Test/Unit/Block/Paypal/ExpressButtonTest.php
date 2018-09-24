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

namespace Payone\Core\Test\Unit\Block\Paypal;

use Payone\Core\Block\Paypal\ExpressButton as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ExpressButtonTest extends BaseTestCase
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
     * @var ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeResolver;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $this->localeResolver = $this->getMockBuilder(ResolverInterface::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'localeResolver' => $this->localeResolver
        ]);
    }

    public function testGetAlias()
    {
        $result = $this->classToTest->getAlias();
        $expected = 'payone.block.paypal.expressbutton';
        $this->assertEquals($expected, $result);
    }

    public function testGetPayPalExpressLink()
    {
        $expected = 'http://testdomain.com';
        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getPayPalExpressLink();
        $this->assertEquals($expected, $result);
    }

    public function testGetPayPalExpressLogoUrl()
    {
        $this->localeResolver->method('getLocale')->willReturn('de_DE');

        $result = $this->classToTest->getPayPalExpressLogoUrl();
        $expected = 'https://www.paypal.com/de_DE/i/btn/btn_xpressCheckout.gif';
        $this->assertEquals($expected, $result);
    }

    public function testGetPayPalExpressLogoUrlDefault()
    {
        $this->localeResolver->method('getLocale')->willReturn(null);

        $result = $this->classToTest->getPayPalExpressLogoUrl();
        $expected = 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif';
        $this->assertEquals($expected, $result);
    }
}
