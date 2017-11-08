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

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Helper\Database;
use Payone\Core\Model\Api\Request\Consumerscore as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\ResourceModel\CheckedAddresses;
use Payone\Core\Model\Source\AddressCheckType;
use Payone\Core\Model\Source\CreditratingCheckType;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ConsumerscoreTest extends BaseTestCase
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
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    /**
     * @var CheckedAddresses|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressesChecked;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getSequenceNumber')->willReturn('0');

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->addressesChecked = $this->getMockBuilder(CheckedAddresses::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'databaseHelper' => $databaseHelper,
            'apiHelper' => $this->apiHelper,
            'shopHelper' => $this->shopHelper,
            'addressesChecked' => $this->addressesChecked
        ]);
    }

    public function testSendRequest()
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

        $this->addressesChecked->method('wasAddressCheckedBefore')->willReturn(false);

        $response = [
            'status' => 'VALID',
            'score'  => 'G',
        ];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'creditrating', 'payone_protect', null, true],
                ['mode', 'creditrating', 'payone_protect', null, 'test'],
                ['aid', 'global', 'payone_general', null, '12345'],
                ['addresscheck', 'creditrating', 'payone_protect', null, 'test'],
                ['type', 'creditrating', 'payone_protect', null, 'test'],
            ]);

        $result = $this->classToTest->sendRequest($address);
        $this->assertArrayHasKey('status', $result);
    }

    public function testSendRequestBoniversum()
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

        $this->addressesChecked->method('wasAddressCheckedBefore')->willReturn(false);

        $response = [
            'status' => 'VALID',
            'score'  => 'U',
        ];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $this->shopHelper->expects(ConsumerscoreTest::any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'creditrating', 'payone_protect', null, true],
                ['mode', 'creditrating', 'payone_protect', null, 'test'],
                ['aid', 'global', 'payone_general', null, '12345'],
                ['addresscheck', 'creditrating', 'payone_protect', null, 'test'],
                ['type', 'creditrating', 'payone_protect', null, CreditratingCheckType::BONIVERSUM_VERITA],
            ]);

        /** @var AddressInterface $address */
        $result = $this->classToTest->sendRequest($address);
        ConsumerscoreTest::assertArrayHasKey('status', $result);
        ConsumerscoreTest::assertEquals(
            AddressCheckType::BONIVERSUM_PERSON,
            $this->classToTest->getParameter('addresschecktype'),
            'Check types do not match!'
        );
    }

    public function testSendRequestTrue()
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

        $this->addressesChecked->method('wasAddressCheckedBefore')->willReturn(true);

        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['enabled', 'creditrating', 'payone_protect', null, true],
                ['mode', 'creditrating', 'payone_protect', null, 'test'],
                ['aid', 'global', 'payone_general', null, '12345'],
                ['addresscheck', 'creditrating', 'payone_protect', null, 'test'],
                ['type', 'creditrating', 'payone_protect', null, 'test'],
            ]);

        $result = $this->classToTest->sendRequest($address);
        $this->assertTrue($result);
    }

    public function testSendRequestNotNeeded()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $this->shopHelper->method('getConfigParam')->willReturn(false);

        $result = $this->classToTest->sendRequest($address);
        $this->assertTrue($result);
    }
}
