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
 * PHP version 7
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Plugins;

use Payone\Core\Model\Plugins\ShippingInformationManagement as ClassToTest;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Payone\Core\Model\Risk\Addresscheck;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Addresscheck as AddresscheckHelper;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Quote\Api\Data\AddressExtension;

class ShippingInformationManagementTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);
    }

    public function testBeforeSaveAddressInformation()
    {
        $source = $this->getMockBuilder(ShippingInformationManagement::class)->disableOriginalConstructor()->getMock();

        $extensionAttributes = $this->getMockBuilder(AddressExtension::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGender', 'getDateofbirth'])
            ->getMock();
        $extensionAttributes->method('getGender')->willReturn(2);
        $extensionAttributes->method('getDateofbirth')->willReturn('12/12/1977');

        $shippingAddress = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $shippingAddress->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $addressInformation = $this->getMockBuilder(ShippingInformationInterface::class)->disableOriginalConstructor()->getMock();
        $addressInformation->method('getShippingAddress')->willReturn($shippingAddress);

        $result = $this->classToTest->beforeSaveAddressInformation($source, '12345', $addressInformation);
        $this->assertNull($result);
    }
}
