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

use Magento\Quote\Model\Quote;
use Payone\Core\Helper\Database;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Api\Request\Managemandate as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Payment\Model\Info;
use Payone\Core\Helper\Api;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ManagemandateTest extends BaseTestCase
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

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getPayoneUserIdByCustNr')->willReturn('15');

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'databaseHelper' => $databaseHelper,
            'shopHelper' => $this->shopHelper,
            'apiHelper' => $this->apiHelper
        ]);
    }

    public function testSendRequest()
    {
        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();
        $paymentInfo->method('getAdditionalInformation')->willReturn('test value');

        $payment = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getInfoInstance')->willReturn($paymentInfo);

        $customer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();
        $customer->method('getId')->willReturn('12345');
        $customer->method('getGender')->willReturn('m');
        $customer->method('getEmail')->willReturn('test@test.de');
        $customer->method('getDob')->willReturn('20000101');

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

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'getQuoteCurrencyCode', 'getBillingAddress'])
            ->getMock();
        $quote->method('getCustomer')->willReturn($customer);
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->method('getBillingAddress')->willReturn($address);

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($payment, $quote);
        $this->assertArrayHasKey('status', $result);
    }
}
