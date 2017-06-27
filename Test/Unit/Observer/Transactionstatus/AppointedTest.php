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

namespace Payone\Core\Test\Unit\Observer\Transactionstatus;

use Payone\Core\Observer\Transactionstatus\Appointed as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;


class AppointedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var OrderSender|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderSender;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->orderSender = $this->getMockBuilder(OrderSender::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'orderSender' => $this->orderSender
        ]);
    }

    public function testExecute()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $observer->method('getOrder')->willReturn($order);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $exception = new \Exception();
        $this->orderSender->method('send')->willThrowException($exception);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $observer->method('getOrder')->willReturn($order);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteNoOrder()
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getOrder'])->getMock();
        $observer->method('getOrder')->willReturn(null);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }
}
