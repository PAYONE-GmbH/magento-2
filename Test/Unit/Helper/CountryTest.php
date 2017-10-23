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

use Payone\Core\Helper\Country;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\Country as CoreCountry;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class CountryTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Country
     */
    private $country;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var CoreCountry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $coreCountry;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->coreCountry = $this->getMockBuilder(CoreCountry::class)->disableOriginalConstructor()->getMock();
        $this->coreCountry->method('getName')->willReturn('Deutschland');

        $this->country = $this->objectManager->getObject(Country::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'country' => $this->coreCountry
        ]);
    }

    public function testGetCountryNameByIso2()
    {
        $this->coreCountry->method('loadByCode')->willReturn($this->coreCountry);
        $result = $this->country->getCountryNameByIso2('DE');
        $expected = 'Deutschland';
        $this->assertEquals($expected, $result);
    }

    public function testGetCountryNameByIso2ReturnFalse()
    {
        $this->coreCountry->method('loadByCode')->willReturn(false);
        $result = $this->country->getCountryNameByIso2('XY');
        $expected = false;
        $this->assertEquals($expected, $result);
    }

    public function testGetDebitSepaCountriesEmpty()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_payment/payone_debit/sepa_country', ScopeInterface::SCOPE_STORE, null, null]
                ]
            );
        $result = $this->country->getDebitSepaCountries();
        $expected = [];
        $this->assertEquals($expected, $result);
    }

    public function testGetDebitSepaCountries()
    {
        $this->coreCountry->method('loadByCode')->willReturn($this->coreCountry);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_payment/payone_debit/sepa_country', ScopeInterface::SCOPE_STORE, null, 'DE']
                ]
            );
        $result = $this->country->getDebitSepaCountries();
        $expected = [['id' => 'DE', 'title' => 'Deutschland']];
        $this->assertEquals($expected, $result);
    }

    public function testIsStateNeeded()
    {
        $result = Country::isStateNeeded('US');
        $this->assertTrue($result);

        $result = Country::isStateNeeded('NL');
        $this->assertFalse($result);
    }
}
