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

use Payone\Core\Helper\Order;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\Database;
use Payone\Core\Helper\Customer;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate;
use Magento\Directory\Model\Region;

class OrderTestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var OrderCore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCore;

    /**
     * @var Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerHelper;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getOrderIncrementIdByTxid')->willReturn('000000001');

        $this->customerHelper = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();

        $this->orderCore = $this->getMockBuilder(OrderCore::class)->disableOriginalConstructor()->getMock();
        $this->orderCore->method('loadByIncrementId')->willReturn($this->orderCore);
        $orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $orderFactory->method('create')->willReturn($this->orderCore);

        $this->order = $this->objectManager->getObject(Order::class, [
            'databaseHelper' => $databaseHelper,
            'customerHelper' => $this->customerHelper,
            'orderFactory' => $orderFactory
        ]);
    }

    public function testGetOrderByTxid()
    {
        $this->orderCore->method('getId')->willReturn('1');

        $result = $this->order->getOrderByTxid('238');
        $this->assertInstanceOf(OrderCore::class, $result);
    }

    public function testGetOrderByTxidNull()
    {
        $this->orderCore->method('getId')->willReturn(false);

        $result = $this->order->getOrderByTxid('238');
        $this->assertNull($result);
    }

    public function testGetShippingMethod()
    {
        $expected = 'free_free';

        $rate1 = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPrice', 'getCode'])
            ->getMock();
        $rate2 = clone $rate1;
        $rate1->method('getPrice')->willReturn('5.00');
        $rate1->method('getCode')->willReturn('not_free');
        $rate2->method('getPrice')->willReturn('0.00');
        $rate2->method('getCode')->willReturn($expected);
        $rates = [
            'key' => [
                $rate1,
                $rate2
            ]
        ];

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getGroupedAllShippingRates')->willReturn($rates);

        $result = $this->order->getShippingMethod($quote, $address);
        $this->assertEquals($expected, $result);
    }

    public function testGetShippingMethodFalse()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getGroupedAllShippingRates')->willReturn([]);

        $result = $this->order->getShippingMethod($quote, $address);
        $this->assertFalse($result);
    }

    public function testSetShippingMethod()
    {
        $rate = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPrice', 'getCode'])
            ->getMock();
        $rate->method('getPrice')->willReturn('0.00');
        $rate->method('getCode')->willReturn('free_free');
        $rates = ['key' => [$rate]];

        $expected = 'free_free';

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getGroupedAllShippingRates')->willReturn($rates);
        $address->method('getShippingMethod')->willReturn($expected);

        $result = $this->order->setShippingMethod($address, $quote);
        $this->assertEquals($expected, $result->getShippingMethod());
    }

    public function testSetShippingMethodException()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getGroupedAllShippingRates')->willReturn([]);

        $this->setExpectedException(LocalizedException::class);
        $this->order->setShippingMethod($address, $quote);
    }

    public function testFillSingleAddress()
    {
        $address = $this->objectManager->getObject(Address::class);
        $firstname = 'Paul';
        $lastname = 'Tester';
        $street = 'Washington Blvd 13';
        $city = 'San Diego';
        $zip = '12345';
        $country = 'US';
        $state = 'CA';
        $regionId = '5';

        $region = $this->getMockBuilder(Region::class)->disableOriginalConstructor()->getMock();
        $region->method('getId')->willReturn($regionId);
        $this->customerHelper->method('getRegion')->willReturn($region);

        $result = $this->order->fillSingleAddress($address, $firstname, $lastname, $street, $city, $zip, $country, $state);
        $this->assertEquals($firstname, $result->getFirstname());
        $this->assertEquals($lastname, $result->getLastname());
        $this->assertEquals($street, $result->getStreet()[0]);
        $this->assertEquals($city, $result->getCity());
        $this->assertEquals($zip, $result->getPostcode());
        $this->assertEquals($country, $result->getCountryId());
        $this->assertEquals($regionId, $result->getRegionId());
    }
}
