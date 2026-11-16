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

namespace Payone\Core\Test\Unit\Model\TransactionStatus;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\TransactionStatus\Mapping as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Payone\Core\Helper\Payment;
use Payone\Core\Helper\Database;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

#[AllowMockObjectsWithoutExpectations]
class MappingTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('getStatusMappingByCode')->willReturn([
            'paid' => 'new'
        ]);
        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getStateByStatus')->willReturn('pending');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'paymentHelper' => $paymentHelper,
            'databaseHelper' => $databaseHelper
        ]);
    }

    public function testHandleMapping()
    {
        $payment = $this->getMockBuilder(OrderPayment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethod')->willReturn(PayoneConfig::METHOD_CREDITCARD);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getPayment')->willReturn($payment);
        $action = 'paid';

        $result = $this->classToTest->handleMapping($order, $action);
        $this->assertNull($result);
    }

    public function testHandleMappingCanceledOrderKeepsStatus()
    {
        $historyRepository = $this->getMockBuilder(OrderStatusHistoryRepositoryInterface::class)->getMock();
        $historyRepository->expects($this->once())->method('save')->with($this->callback(function ($history) {
            return $history->getParentId() === 42
                && $history->getStatus() === Order::STATE_CANCELED
                && $history->getComment() === 'Received PAYONE status "paid".'
                && $history->getEntityName() === Order::ENTITY
                && $history->getIsCustomerNotified() === 0;
        }));
        $classToTest = $this->getClassToTest('processing', 'processing', $historyRepository);

        $order = $this->getCanceledOrder();
        $order->method('getId')->willReturn(42);
        $order->method('getStatus')->willReturn(Order::STATE_CANCELED);
        $order->expects($this->never())->method('addStatusToHistory');
        $order->expects($this->never())->method('setStatus');
        $order->expects($this->never())->method('setState');
        $order->expects($this->never())->method('save');

        $result = $classToTest->handleMapping($order, 'paid');
        $this->assertNull($result);
    }

    public function testHandleMappingCanceledOrderToCanceledStateStatus()
    {
        $historyRepository = $this->getMockBuilder(OrderStatusHistoryRepositoryInterface::class)->getMock();
        $historyRepository->expects($this->never())->method('save');
        $classToTest = $this->getClassToTest('canceled_paid', Order::STATE_CANCELED, $historyRepository);

        $order = $this->getCanceledOrder();
        $order->expects($this->once())->method('addStatusToHistory')->with('canceled_paid', $this->anything(), false);
        $order->expects($this->once())->method('setState')->with(Order::STATE_CANCELED);
        $order->expects($this->once())->method('save');

        $result = $classToTest->handleMapping($order, 'paid');
        $this->assertNull($result);
    }

    private function getClassToTest($sMappedStatus, $sState, $historyRepository)
    {
        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('getStatusMappingByCode')->willReturn([
            'paid' => $sMappedStatus
        ]);
        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getStateByStatus')->willReturn($sState);

        $historyFactory = $this->getMockBuilder(OrderStatusHistoryInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $historyFactory->method('create')->willReturnCallback(function () {
            return $this->objectManager->getObject(History::class);
        });

        return $this->objectManager->getObject(ClassToTest::class, [
            'paymentHelper' => $paymentHelper,
            'databaseHelper' => $databaseHelper,
            'historyFactory' => $historyFactory,
            'historyRepository' => $historyRepository
        ]);
    }

    private function getCanceledOrder()
    {
        $payment = $this->getMockBuilder(OrderPayment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethod')->willReturn(PayoneConfig::METHOD_ADVANCE_PAYMENT);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getPayment')->willReturn($payment);
        $order->method('isCanceled')->willReturn(true);

        return $order;
    }
}
