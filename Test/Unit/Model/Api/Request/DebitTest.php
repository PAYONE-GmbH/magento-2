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

use Magento\Sales\Model\Order;
use Payone\Core\Helper\Database;
use Payone\Core\Model\Api\Request\Debit as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Payment\Model\Info;
use Payone\Core\Helper\Api;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Helper\Shop;

class DebitTest extends \PHPUnit_Framework_TestCase
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
        $objectManager = new ObjectManager($this);

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getSequenceNumber')->willReturn('0');

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('handleSubstituteReplacement')->willReturn('test text');

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getConfigParam')->willReturn('test');

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'databaseHelper' => $databaseHelper,
            'apiHelper' => $this->apiHelper,
            'toolkitHelper' => $toolkitHelper,
            'shopHelper' => $shopHelper
        ]);
    }

    public function testSendRequest()
    {
        $invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        $invoice->method('getIncrementId')->willReturn('12345');
        $invoice->method('getId')->willReturn('12345');

        $creditmemo = $this->getMockBuilder(Creditmemo::class)->disableOriginalConstructor()->getMock();
        $creditmemo->method('getInvoice')->willReturn($invoice);
        $creditmemo->method('getIncrementId')->willReturn('12345');

        $payment = $this->getMockBuilder(PayoneMethod::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOperationMode', 'getCreditmemo'])
            ->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getCreditmemo')->willReturn($creditmemo);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRealOrderId', 'getOrderCurrencyCode', 'getIncrementId', 'getId', 'getCustomerId'])
            ->getMock();
        $order->method('getRealOrderId')->willReturn('54321');
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getIncrementId')->willReturn('12345');
        $order->method('getId')->willReturn('12345');
        $order->method('getCustomerId')->willReturn('12345');

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn('12-345');

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $result = $this->classToTest->sendRequest($payment, $paymentInfo, 100);
        $this->assertEquals($response, $result);
    }
}
