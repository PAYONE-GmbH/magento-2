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

use Magento\Framework\Exception\LocalizedException;
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
use Magento\Sales\Model\Order\Item;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class DebitTest extends BaseTestCase
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
        $databaseHelper->method('getSequenceNumber')->willReturn('0');

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('handleSubstituteReplacement')->willReturn('test text');

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper->method('getConfigParam')->willReturn('test');

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'databaseHelper' => $databaseHelper,
            'apiHelper' => $this->apiHelper,
            'toolkitHelper' => $toolkitHelper,
            'shopHelper' => $this->shopHelper
        ]);
    }

    protected function getPaymentMock()
    {
        $invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        $invoice->method('getIncrementId')->willReturn('12345');
        $invoice->method('getId')->willReturn('12345');

        $creditmemo = $this->getMockBuilder(Creditmemo::class)->disableOriginalConstructor()->getMock();
        $creditmemo->method('getInvoice')->willReturn($invoice);
        $creditmemo->method('getIncrementId')->willReturn('12345');

        $payment = $this->getMockBuilder(PayoneMethod::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOperationMode', 'getCreditmemo', 'hasCustomConfig', 'getCustomConfigParam'])
            ->getMock();
        $payment->method('getOperationMode')->willReturn('test');
        $payment->method('getCreditmemo')->willReturn($creditmemo);
        $payment->method('hasCustomConfig')->willReturn(true);
        $payment->method('getCustomConfigParam')->willReturn('test');

        return $payment;
    }

    protected function getOrderMock()
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRealOrderId', 'getOrderCurrencyCode', 'getIncrementId', 'getId', 'getCustomerId', 'getPayoneRefundIban', 'getPayoneRefundBic', 'getAllItems'])
            ->getMock();
        $order->method('getRealOrderId')->willReturn('54321');
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getIncrementId')->willReturn('12345');
        $order->method('getId')->willReturn('12345');
        $order->method('getCustomerId')->willReturn('12345');
        return $order;
    }

    public function testSendRequest()
    {
        $payment = $this->getPaymentMock();

        $order = $this->getOrderMock();
        $order->method('getPayoneRefundIban')->willReturn('DE85123456782599100003');
        $order->method('getPayoneRefundBic')->willReturn('TESTTEST');

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn('12-345');

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);
        $this->apiHelper->method('isInvoiceDataNeeded')->willReturn(true);

        $result = $this->classToTest->sendRequest($payment, $paymentInfo, 100);
        $this->assertEquals($response, $result);
    }

    public function testSendRequestPositions()
    {
        $requestparam = ['items' => ['id' => ['qty' => 1]], 'shipping_amount' => 5, 'payone_iban' => 'DE85123456782599100003', 'payone_bic' => 'TESTTEST'];
        $this->shopHelper->method('getRequestParameter')->willReturn($requestparam);

        $payment = $this->getPaymentMock();

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemId', 'getProductId', 'getQtyOrdered'])
            ->getMock();
        $item->method('getItemId')->willReturn('id');
        $item->method('getProductId')->willReturn('sku');
        $item->method('getQtyOrdered')->willReturn(2);

        $item_missing = $this->getMockBuilder(Item::class)->disableOriginalConstructor()->getMock();
        $item_missing->method('getItemId')->willReturn('missing');

        $order = $this->getOrderMock();
        $order->method('getAllItems')->willReturn([$item, $item_missing]);

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn('12-345');

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);
        $this->apiHelper->method('isInvoiceDataNeeded')->willReturn(true);

        $result = $this->classToTest->sendRequest($payment, $paymentInfo, 100);
        $this->assertEquals($response, $result);
    }

    public function testSendRequestIbanException()
    {
        $payment = $this->getPaymentMock();

        $order = $this->getOrderMock();
        $order->method('getPayoneRefundIban')->willReturn('12345');
        $order->method('getPayoneRefundBic')->willReturn('TESTTEST');

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn('12-345');

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);
        $this->apiHelper->method('isInvoiceDataNeeded')->willReturn(true);

        $this->expectException(LocalizedException::class);
        $this->classToTest->sendRequest($payment, $paymentInfo, 100);
    }

    public function testSendRequestBicException()
    {
        $payment = $this->getPaymentMock();

        $order = $this->getOrderMock();
        $order->method('getPayoneRefundIban')->willReturn('DE85123456782599100003');
        $order->method('getPayoneRefundBic')->willReturn('12345');

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn('12-345');

        $response = ['status' => 'VALID'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);
        $this->apiHelper->method('isInvoiceDataNeeded')->willReturn(true);

        $this->expectException(LocalizedException::class);
        $this->classToTest->sendRequest($payment, $paymentInfo, 100);
    }
}
