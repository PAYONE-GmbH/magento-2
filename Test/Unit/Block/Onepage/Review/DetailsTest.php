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

namespace Payone\Core\Test\Unit\Block\Onepage\Review;

use Payone\Core\Block\Onepage\Review\Details as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class DetailsTest extends BaseTestCase
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

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);
        $this->classToTest->setCustomQuote($this->quote);
    }

    public function testGetTotals()
    {
        $expected = ['subtotal' => 123.00];
        $this->quote->method('getTotals')->willReturn($expected);

        $result = $this->classToTest->getTotals();
        $this->assertEquals($expected, $result);
    }

    public function testGetAddress()
    {
        $expected = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $this->quote->method('getShippingAddress')->willReturn($expected);

        $result = $this->classToTest->getAddress();
        $this->assertEquals($expected, $result);
    }
}
