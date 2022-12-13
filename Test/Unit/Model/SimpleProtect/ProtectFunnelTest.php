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

namespace Payone\Core\Test\Unit\Model\SimpleProtect;

use Payone\Core\Model\Api\Request\Addresscheck;
use Payone\Core\Model\Api\Request\Consumerscore;
use Payone\Core\Model\SimpleProtect\ProtectFunnel as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Model\Source\AddressCheckType;
use Payone\Core\Model\Source\CreditratingCheckType;
use Magento\Framework\Exception\InputException;
use Payone\Core\Model\Api\Response\AddresscheckResponse;
use Payone\Core\Model\Api\Response\ConsumerscoreResponse;

class ProtectFunnelTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Addresscheck|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addresscheck;

    /**
     * @var Consumerscore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consumerscore;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $this->consumerscore = $this->getMockBuilder(Consumerscore::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'addresscheck' => $this->addresscheck,
            'consumerscore' => $this->consumerscore,
        ]);
    }

    public function testExecuteAddresscheckPersonOutsideGermany()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('US');

        $result = $this->classToTest->executeAddresscheck($address, 'test', AddressCheckType::PERSON);
        $this->assertFalse($result);
    }

    public function testExecuteAddresscheckBasic()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('ZZ');

        $result = $this->classToTest->executeAddresscheck($address, 'test', AddressCheckType::BASIC);
        $this->assertFalse($result);
    }

    public function testValidateAddresscheckType()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');

        $this->expectException(InputException::class);
        $this->classToTest->executeAddresscheck($address, 'test', 'ZZZZ');
    }

    public function testExecuteAddresscheck()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');

        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'VALID']);

        $result = $this->classToTest->executeAddresscheck($address, 'test', AddressCheckType::BASIC);
        $this->assertInstanceOf(AddresscheckResponse::class, $result);
    }

    public function testExecuteConsumerscoreOutsideGermany()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('US');

        $result = $this->classToTest->executeConsumerscore($address, 'test', CreditratingCheckType::BONIVERSUM_VERITA);
        $this->assertTrue($result);
    }

    public function testValidateConsumerscoreType()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');

        $this->expectException(InputException::class);
        $this->classToTest->executeConsumerscore($address, 'test', 'ZZZZ');
    }

    public function testExecuteConsumerscore()
    {
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID']);

        $result = $this->classToTest->executeConsumerscore($address, 'test', CreditratingCheckType::BONIVERSUM_VERITA);
        $this->assertInstanceOf(ConsumerscoreResponse::class, $result);
    }
}
