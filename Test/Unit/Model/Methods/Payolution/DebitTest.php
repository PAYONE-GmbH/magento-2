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

namespace Payone\Core\Test\Unit\Model\Methods\Payolution;

use Magento\Store\Model\Store;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Model\Methods\Payolution\Debit as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Info;
use Payone\Core\Model\PayoneConfig;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Payone\Core\Model\Api\Request\Genericpayment\PreCheck;
use Magento\Sales\Model\Order;
use Payone\Core\Model\Api\Request\Authorization;
use Magento\Framework\DataObject;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class DebitTest extends BaseTestCase
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
     * @var bool
     */
    protected $needsObjectManagerMock = true;

    /**
     * @var PreCheck|\PHPUnit_Framework_MockObject_MockObject
     */
    private $precheckRequest;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $info = $this->getMockBuilder(Info::class)->disableOriginalConstructor()->getMock();
        $info->method('getAdditionalInformation')->willReturn('value');

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('getAdditionalDataEntry')->willReturn('value');

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getStore')->willReturn($store);

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->precheckRequest = $this->getMockBuilder(PreCheck::class)->disableOriginalConstructor()->getMock();
        $authorizationRequest = $this->getMockBuilder(Authorization::class)->disableOriginalConstructor()->getMock();
        $authorizationRequest->method('sendRequest')->willReturn(['status' => 'APPROVED', 'txid' => '12345']);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'toolkitHelper' => $toolkitHelper,
            'checkoutSession' => $checkoutSession,
            'precheckRequest' => $this->precheckRequest,
            'authorizationRequest' => $authorizationRequest
        ]);
        $this->classToTest->setInfoInstance($info);
    }

    public function testAuthorize()
    {
        $response = ['status' => 'OK', 'workorderid' => 'WORKORDER'];
        $this->precheckRequest->method('sendRequest')->willReturn($response);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation'])
            ->addMethods(['getOrder'])
            ->getMock();
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getAdditionalInformation')->willReturn(['iban' => '12345']);

        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $order->method('getPayment')->willReturn($payment);
        $order->method('getStore')->willReturn($store);

        $result = $this->classToTest->authorize($payment, 100);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testAssignData()
    {
        $data = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testGetSubTypeSpecificParameters()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getSubTypeSpecificParameters($order);
        $expected = [
            'iban' => 'value',
            'bic' => 'value',
        ];
        $this->assertEquals($expected, $result);
    }
}
