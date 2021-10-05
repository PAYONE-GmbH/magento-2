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
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Methods;

use Payone\Core\Helper\Toolkit;
use Payone\Core\Model\Methods\ApplePay as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\ApplePay;
use Magento\Framework\DataObject;

class ApplePayTest extends BaseTestCase
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ApplePay|\PHPUnit\Framework\MockObject\MockObject
     */
    private $applePayHelper;

    /**
     * @var Order\Payment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $info;

    /**
     * @var array
     */
    private $token = ['paymentData' => [
        'data' => 'value',
        'signature' => 'value',
        'version' => 'value',
        'header' => [
            'ephemeralPublicKey' => 'value',
            'publicKeyHash' => 'value',
            'transactionId' => 'value',
        ],
    ]];

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->info = $this->getMockBuilder(Order\Payment::class)->disableOriginalConstructor()->setMethods(['getAdditionalInformation'])->getMock();

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('getAdditionalDataEntry')->willReturn($this->token);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $this->applePayHelper = $this->getMockBuilder(ApplePay::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'toolkitHelper' => $toolkitHelper,
            'scopeConfig' => $this->scopeConfig,
            'applePayHelper' => $this->applePayHelper,
        ]);
        $this->classToTest->setInfoInstance($this->info);
    }

    public function testIsAvailableParent()
    {
        $this->scopeConfig->method('getValue')->willReturn(0);

        $result = $this->classToTest->isAvailable();
        $this->assertFalse($result);
    }

    public function testIsAvailableTrue()
    {
        $this->scopeConfig->method('getValue')->willReturn(1);

        $this->applePayHelper->method("isConfigurationComplete")->willReturn(true);

        $result = $this->classToTest->isAvailable();
        $this->assertTrue($result);
    }

    public function testIsAvailableFalse()
    {
        $this->scopeConfig->method('getValue')->willReturn(1);

        $this->applePayHelper->method("isConfigurationComplete")->willReturn(false);

        $result = $this->classToTest->isAvailable();
        $this->assertFalse($result);
    }

    public function testGetPaymentSpecificParameters()
    {
        $this->info->method('getAdditionalInformation')->willReturn(json_encode($this->token));

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getPaymentSpecificParameters($order);

        $this->assertTrue(is_array($result));
        $this->assertCount(8, $result);
    }

    public function testGetPaymentSpecificParametersException()
    {
        $this->info->method('getAdditionalInformation')->willReturn(null);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $this->expectException(\Exception::class);
        $result = $this->classToTest->getPaymentSpecificParameters($order);
    }

    public function testAssignData()
    {
        $data = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }
}
