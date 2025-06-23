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

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\Customer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Address;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\Region;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class CustomerTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $coreCustomer;

    /**
     * @var Region|\PHPUnit_Framework_MockObject_MockObject
     */
    private $region;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSession;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->coreCustomer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getCustomer')->willReturn($this->coreCustomer);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods(['getPayoneGuestGender', 'getPayoneGuestDateofbirth'])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'loadByName', 'loadByCode'])
            ->addMethods(['getCode'])
            ->getMock();
        $regionFactory = $this->getMockBuilder(RegionFactory::class)->disableOriginalConstructor()->getMock();
        $regionFactory->method('create')->willReturn($this->region);

        $this->customer = $this->objectManager->getObject(Customer::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'checkoutSession' => $this->checkoutSession,
            'regionFactory' => $regionFactory
        ]);
    }

    public function testGetCustomerGenderFemale()
    {
        $this->checkoutSession->method('getPayoneGuestGender')->willReturn(null);
        $this->coreCustomer->method('getGender')->willReturn(2);
        $result = $this->customer->getCustomerGender();
        $expected = 'f';
        $this->assertEquals($expected, $result);
    }

    public function testGetCustomerGenderMale()
    {
        $this->checkoutSession->method('getPayoneGuestGender')->willReturn(null);
        $this->coreCustomer->method('getGender')->willReturn(1);
        $result = $this->customer->getCustomerGender();
        $expected = 'm';
        $this->assertEquals($expected, $result);
    }

    public function testGetCustomerGenderNull()
    {
        $this->checkoutSession->method('getPayoneGuestGender')->willReturn(null);
        $this->coreCustomer->method('getGender')->willReturn(null);
        $result = $this->customer->getCustomerGender();
        $this->assertNull($result);
    }

    public function testGetCustomerGenderSession()
    {
        $this->checkoutSession->method('getPayoneGuestGender')->willReturn(3);
        $result = $this->customer->getCustomerGender();
        $expected = 'd';
        $this->assertEquals($expected, $result);
    }

    public function testCustomerHasGivenBirthday()
    {
        $this->checkoutSession->method('getPayoneGuestDateofbirth')->willReturn(null);
        $expected = '19851130';
        $this->coreCustomer->method('getDob')->willReturn('11/30/1985');
        $result = $this->customer->getCustomerBirthday();
        $this->assertEquals($expected, $result);
    }

    public function testCustomerHasGivenBirthdayNull()
    {
        $this->checkoutSession->method('getPayoneGuestDateofbirth')->willReturn(null);
        $this->coreCustomer->method('getDob')->willReturn(null);
        $result = $this->customer->getCustomerBirthday();
        $this->assertNull($result);
    }

    public function testCustomerHasGivenBirthdaySession()
    {
        $this->checkoutSession->method('getPayoneGuestDateofbirth')->willReturn('11/09/1985');
        $expected = '19851109';
        $result = $this->customer->getCustomerBirthday();
        $this->assertEquals($expected, $result);
    }

    public function testGetRegionCode()
    {
        $expected = 'CA';

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getRegionCode')->willReturn('California');
        $address->method('getCountryId')->willReturn('US');

        $this->region->method('getId')->willReturn('5');
        $this->region->method('getCode')->willReturn($expected);

        $result = $this->customer->getRegionCode($address);
        $this->assertEquals($expected, $result);
    }

    public function testGetRegionCodeDirectPass()
    {
        $expected = 'CA';

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getRegionCode')->willReturn($expected);

        $result = $this->customer->getRegionCode($address);
        $this->assertEquals($expected, $result);
    }

    public function testGetRegion()
    {
        $this->region->method('getId')->willReturn('5');
        $result = $this->customer->getRegion('US', 'CA');
        $this->assertInstanceOf(Region::class, $result);
    }

    public function testGetRegionFalse()
    {
        $result = $this->customer->getRegion('US', 'CA');
        $this->assertFalse($result);
    }

    /**
     * @return array
     */
    public function getGenders()
    {
        return [
            ['1', 'm'],
            ['2', 'f'],
            ['x', '']
        ];
    }

    /**
     * @param int $gender
     * @param string $expected
     *
     * @dataProvider getGenders
     */
    public function testGetGenderParameter($gender, $expected)
    {
        $result = $this->customer->getGenderParameter($gender);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getSalutations()
    {
        return [
            ['1', 'Mr'],
            ['2', 'Mrs'],
            ['x', '']
        ];
    }

    /**
     * @param int $gender
     * @param string $expected
     *
     * @dataProvider getSalutations
     */
    public function testGetSalutationParameter($gender, $expected)
    {
        $result = $this->customer->getSalutationParameter($gender);
        $this->assertEquals($expected, $result);
    }
}
