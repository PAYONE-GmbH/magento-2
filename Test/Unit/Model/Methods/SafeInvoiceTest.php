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

namespace Payone\Core\Test\Unit\Model\Methods;

use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\Methods\SafeInvoice as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\DataObject;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Model\Api\Request\Authorization;
use Magento\Payment\Model\Info;

class SafeInvoiceTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Authorization|\PHPUnit_Framework_MockObject_MockObject
     */
    private $authorizationRequest;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $info = $this->getMockBuilder(InfoInterface::class)->disableOriginalConstructor()->getMock();
        $info->method('getAdditionalInformation')->willReturn('19010101');

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('getAdditionalDataEntry')->willReturn('12');

        $this->authorizationRequest = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'toolkitHelper' => $toolkitHelper,
            'authorizationRequest' => $this->authorizationRequest,
        ]);
        $this->classToTest->setInfoInstance($info);
    }

    public function testGetPaymentSpecificParameters()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCompany')->willReturn('Testcompany Ltd');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getBillingAddress')->willReturn($address);

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $expected = ['clearingsubtype' => 'POV', 'birthday' => '19010101', 'businessrelation' => 'b2b'];
        $this->assertEquals($expected, $result);
    }

    public function testAssignData()
    {
        $data = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testAuthorizeRegistered()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getCustomerId')->willReturn('5');

        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);

        $aResponse = ['status' => 'ERROR', 'errorcode' => '351', 'customermessage' => 'error'];
        $this->authorizationRequest->method('sendRequest')->willReturn($aResponse);

        $this->expectException(LocalizedException::class);
        $this->classToTest->authorize($paymentInfo, 100);
    }

    public function testAuthorizeGuest()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getCustomerId')->willReturn(null);

        $paymentInfo = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);

        $aResponse = ['status' => 'ERROR', 'errorcode' => '351', 'customermessage' => 'error'];
        $this->authorizationRequest->method('sendRequest')->willReturn($aResponse);

        $this->expectException(LocalizedException::class);
        $this->classToTest->authorize($paymentInfo, 100);
    }
}
