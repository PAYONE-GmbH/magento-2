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

namespace Payone\Core\Test\Unit\Model\Api\Request;

use Payone\Core\Model\Api\Request\PaydirektAgreement as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Api;
use Payone\Core\Helper\Customer;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Address;
use Magento\Framework\Url;
use Payone\Core\Helper\Environment;

class PaydirektAgreementTest extends BaseTestCase
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

        $url = $this->getMockBuilder(Url::class)->disableOriginalConstructor()->getMock();
        $url->method('getUrl')->willReturn('test');
        
        $environmentHelper = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $environmentHelper->method('getRemoteIp')->willReturn('1.1.1.1');
        
        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'apiHelper' => $this->apiHelper,
            'customerHelper' => $customerHelper,
            'url' => $url,
            'environmentHelper' => $environmentHelper,
        ]);
    }

    public function testSendAgreementRequest()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $customer = $this->getMockBuilder(CustomerModel::class)->disableOriginalConstructor()->getMock();
        $customer->method('getDefaultBillingAddress')->willReturn($address);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteCurrencyCode', 'getBillingAddress'])
            ->getMock();
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendAgreementRequest($customer, $quote);
        $this->assertArrayHasKey('status', $result);
    }

    public function testSendAgreementRequestNoCustomerAddress()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $customer = $this->getMockBuilder(CustomerModel::class)->disableOriginalConstructor()->getMock();
        $customer->method('getDefaultBillingAddress')->willReturn(false);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteCurrencyCode', 'getBillingAddress'])
            ->getMock();
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->method('getBillingAddress')->willReturn($address);

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendAgreementRequest($customer, $quote);
        $this->assertArrayHasKey('status', $result);
    }
}
