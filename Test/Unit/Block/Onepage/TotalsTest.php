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

namespace Payone\Core\Test\Unit\Block\Onepage;

use Magento\Quote\Model\Quote;
use Payone\Core\Block\Onepage\Totals as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Payone\Core\Helper\Base;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class TotalsTest extends BaseTestCase
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

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $baseHelper = $this->getMockBuilder(Base::class)->disableOriginalConstructor()->getMock();
        $baseHelper->method('getConfigParam')->willReturn('base');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'baseHelper' => $baseHelper,
        ]);
    }

    public function testNeedDisplayBaseGrandtotal()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseCurrencyCode', 'getQuoteCurrencyCode'])
            ->getMock();
        $quote->method('getBaseCurrencyCode')->willReturn('EUR');
        $quote->method('getQuoteCurrencyCode')->willReturn('GBP');

        $this->classToTest->setCustomQuote($quote);

        $result = $this->classToTest->needDisplayBaseGrandtotal();
        $this->assertTrue($result);
    }

    public function testNeedDisplayBaseGrandtotalFalse()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseCurrencyCode', 'getQuoteCurrencyCode'])
            ->getMock();
        $quote->method('getBaseCurrencyCode')->willReturn('EUR');
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');

        $this->classToTest->setCustomQuote($quote);

        $result = $this->classToTest->needDisplayBaseGrandtotal();
        $this->assertFalse($result);
    }
}
