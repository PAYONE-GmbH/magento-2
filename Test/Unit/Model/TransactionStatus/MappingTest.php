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
use Payone\Core\Helper\Payment;
use Payone\Core\Helper\Database;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

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

    protected function setUp()
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
}
