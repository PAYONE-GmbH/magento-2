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

namespace Payone\Core\Test\Unit\Model\Api\Request;

use Magento\Quote\Model\Quote\Address;
use Payone\Core\Model\Api\Request\Addresscheck as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Api;
use Payone\Core\Model\ResourceModel\CheckedAddresses;
use Payone\Core\Helper\Shop;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class AddresscheckTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apiHelper;

    /**
     * @var CheckedAddresses|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressesChecked;

    /**
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();

        $this->addressesChecked = $this->getMockBuilder(CheckedAddresses::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'apiHelper' => $this->apiHelper,
            'shopHelper' => $this->shopHelper,
            'addressesChecked' => $this->addressesChecked
        ]);
    }

    public function testSendRequestTrueNotEnabled()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $this->shopHelper->method('getConfigParam')->willReturn(false);

        $result = $this->classToTest->sendRequest($address, true);
        $this->assertTrue($result);
    }

    public function testSendRequestTrueNoBilling()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_billing', 'address_check', 'payone_protect', null, 'NO']
            ]);

        $result = $this->classToTest->sendRequest($address, true);
        $this->assertTrue($result);
    }

    public function testSendRequestTrueNoShipping()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_shipping', 'address_check', 'payone_protect', null, 'NO']
            ]);

        $result = $this->classToTest->sendRequest($address, false);
        $this->assertTrue($result);
    }

    public function testSendRequestInvalidTypePE()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('FR');

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_shipping', 'address_check', 'payone_protect', null, 'PE']
            ]);

        $result = $this->classToTest->sendRequest($address, false);
        $expected = ['wrongCountry' => true];
        $this->assertEquals($expected, $result);
    }

    public function testSendRequestInvalidTypeBA()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('XY');

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_shipping', 'address_check', 'payone_protect', null, 'BA']
            ]);

        $result = $this->classToTest->sendRequest($address, false);
        $expected = ['wrongCountry' => true];
        $this->assertEquals($expected, $result);
    }

    public function testSendRequestNotChecked()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');
        $address->method('getFirstname')->willReturn('Paul');
        $address->method('getLastname')->willReturn('Paytest');
        $address->method('getCompany')->willReturn('Testcompany Ltd.');
        $address->method('getStreet')->willReturn(['Teststr. 5', '1st floor']);
        $address->method('getPostcode')->willReturn('12345');
        $address->method('getCity')->willReturn('Berlin');
        $address->method('getRegionCode')->willReturn('Berlin');

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_shipping', 'address_check', 'payone_protect', null, 'PE'],
                ['mode', 'address_check', 'payone_protect', null, 'test'],
                ['aid', 'global', 'payone_general', null, 'PE']
            ]);

        $this->addressesChecked->method('wasAddressCheckedBefore')->willReturn(false);

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($address, false);
        $this->assertEquals($response, $result);
    }

    public function testSendRequestChecked()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');
        $address->method('getFirstname')->willReturn('Paul');
        $address->method('getLastname')->willReturn('Paytest');
        $address->method('getCompany')->willReturn('Testcompany Ltd.');
        $address->method('getStreet')->willReturn(['Teststr. 5', '1st floor']);
        $address->method('getPostcode')->willReturn('12345');
        $address->method('getCity')->willReturn('Berlin');
        $address->method('getRegionCode')->willReturn('Berlin');

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'address_check', 'payone_protect', null, true],
                ['check_shipping', 'address_check', 'payone_protect', null, 'PE'],
                ['mode', 'address_check', 'payone_protect', null, 'test'],
                ['aid', 'global', 'payone_general', null, 'PE']
            ]);

        $this->addressesChecked->method('wasAddressCheckedBefore')->willReturn(true);

        $result = $this->classToTest->sendRequest($address, false);
        $this->assertTrue($result);
    }
}
