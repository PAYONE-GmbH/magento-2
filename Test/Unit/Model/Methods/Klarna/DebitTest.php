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
 * PHP version 8
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2023 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Methods\Klarna;

use Payone\Core\Model\Methods\Klarna\Debit as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Info;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

class DebitTest extends BaseTestCase
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
     * @var bool
     */
    protected $needsObjectManagerMock = true;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'scopeConfig' => $this->scopeConfig
        ]);
    }

    public function testIsAvailable()
    {
        $this->scopeConfig->method('getValue')->willReturn(0);

        $result = $this->classToTest->isAvailable();
        $this->assertFalse($result);
    }

    public function testIsAvailableB2B()
    {
        $this->scopeConfig->method('getValue')->willReturn(1);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCompany')->willReturn("Test GmbH");

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $result = $this->classToTest->isAvailable($quote);
        $this->assertFalse($result);
    }

    public function testIsAvailableNotB2B()
    {
        $this->scopeConfig->method('getValue')->willReturn(1);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCompany')->willReturn(null);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);

        $result = $this->classToTest->isAvailable($quote);
        $this->assertTrue($result);
    }
}
