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

namespace Payone\Core\Test\Unit\Block\Adminhtml\Order\Creditmemo;

use Payone\Core\Block\Adminhtml\Order\Creditmemo\SepaData as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Methods\Creditcard;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote\Payment;

class SepaDataTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $methodInstance = $this->getMockBuilder(Creditcard::class)->disableOriginalConstructor()->getMock();
        $methodInstance->method('needsSepaDataOnDebit')->willReturn(false);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($methodInstance);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneRefundIban', 'getPayoneRefundBic', 'getPayment'])
            ->getMock();
        $order->method('getPayoneRefundIban')->willReturn('DE85123456782599100003');
        $order->method('getPayoneRefundBic')->willReturn('TESTTEST');
        $order->method('getPayment')->willReturn($payment);

        $creditmemo = $this->getMockBuilder(Creditmemo::class)->disableOriginalConstructor()->getMock();
        $creditmemo->method('getOrder')->willReturn($order);

        $registry = $this->getMockBuilder(Registry::class)->disableOriginalConstructor()->getMock();
        $registry->method('registry')->willReturn($creditmemo);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'registry' => $registry
        ]);
    }

    public function testGetCreditmemo()
    {
        $result = $this->classToTest->getCreditmemo();
        $this->assertInstanceOf(Creditmemo::class, $result);
    }

    public function testGetOrder()
    {
        $result = $this->classToTest->getOrder();
        $this->assertInstanceOf(Order::class, $result);
    }

    public function testShowPayoneSepaDataFields()
    {
        $result = $this->classToTest->showPayoneSepaDataFields();
        $expected = false;
        $this->assertEquals($expected, $result);
    }

    public function testGetPrefilledIban()
    {
        $result = $this->classToTest->getPrefilledIban();
        $expected = 'DE85123456782599100003';
        $this->assertEquals($expected, $result);
    }

    public function testGetPrefilledBic()
    {
        $result = $this->classToTest->getPrefilledBic();
        $expected = 'TESTTEST';
        $this->assertEquals($expected, $result);
    }
}
