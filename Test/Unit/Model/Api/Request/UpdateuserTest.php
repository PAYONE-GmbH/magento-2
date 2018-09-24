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
use Magento\Sales\Model\Order;
use Payone\Core\Model\Api\Request\Updateuser as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Customer;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class UpdateuserTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apiHelper;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();

        $customerHelper = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();
        $customerHelper->method('getSalutationParameter')->willReturn('mr');
        $customerHelper->method('getGenderParameter')->willReturn('m');

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'apiHelper' => $this->apiHelper,
            'customerHelper' => $customerHelper
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
        $address->method('getTelephone')->willReturn('0301234567');
        $address->method('getVatId')->willReturn('12345');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getRealOrderId')->willReturn('12345');
        $order->method('getCustomerGender')->willReturn('m');
        $order->method('getCustomerEmail')->willReturn('test@test.com');
        $order->method('getCustomerDob')->willReturn('20000101');
        $order->method('getBillingAddress')->willReturn($address);

        $payment = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_KLARNA);

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($order, $payment, 100);
        $this->assertArrayHasKey('status', $result);
    }
}
