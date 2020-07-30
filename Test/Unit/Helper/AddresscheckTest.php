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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\Addresscheck as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class AddresscheckTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper
        ]);
    }

    public function testIsCheckEnabledDisabled()
    {
        $this->shopHelper->method('getConfigParam')->willReturn(false);

        $result = $this->classToTest->isCheckEnabled(true);
        $this->assertFalse($result);
    }

    public function testIsCheckEnabledDisabledBilling()
    {
        $this->shopHelper->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_billing', 'address_check', 'payone_protect', null, 'NO']
            ]);

        $result = $this->classToTest->isCheckEnabled(true);
        $this->assertFalse($result);
    }

    public function testIsCheckEnabledDisabledShipping()
    {
        $this->shopHelper->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_shipping', 'address_check', 'payone_protect', null, 'NO']
            ]);

        $result = $this->classToTest->isCheckEnabled(false);
        $this->assertFalse($result);
    }

    public function testIsCheckEnabled()
    {
        $this->shopHelper->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_shipping', 'address_check', 'payone_protect', null, 'PE']
            ]);

        $result = $this->classToTest->isCheckEnabled(false);
        $this->assertTrue($result);
    }
}
