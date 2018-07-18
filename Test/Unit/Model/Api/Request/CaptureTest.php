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
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Api\Request\Capture as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Payment\Model\Info;
use Payone\Core\Helper\Api;
use Magento\Sales\Model\Order\Item;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Store\Model\Store;

class CaptureTest extends BaseTestCase
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

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getSequenceNumber')->willReturn('0');

        $this->apiHelper = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'databaseHelper' => $databaseHelper,
            'apiHelper' => $this->apiHelper
        ]);
    }

    public function testSendRequest()
    {
        $invoice = ['items' => ['id' => 1]];
        $this->shopHelper->method('getRequestParameter')->willReturn($invoice);

        $payment = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemId', 'getProductId', 'getQtyOrdered'])
            ->getMock();
        $item->method('getItemId')->willReturn('id');
        $item->method('getProductId')->willReturn('sku');
        $item->method('getQtyOrdered')->willReturn(2);

        $item_missing = $this->getMockBuilder(Item::class)->disableOriginalConstructor()->getMock();
        $item_missing->method('getItemId')->willReturn('missing');

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getCode')->willReturn('default');

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRealOrderId', 'getOrderCurrencyCode', 'getAllItems', 'getStore'])
            ->getMock();
        $order->method('getRealOrderId')->willReturn('54321');
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getAllItems')->willReturn([$item, $item_missing]);
        $order->method('getStore')->willReturn($store);

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn('12345');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);
        $this->apiHelper->method('isInvoiceDataNeeded')->willReturn(true);

        $result = $this->classToTest->sendRequest($payment, $paymentInfo, 100);
        $this->assertArrayHasKey('status', $result);
    }

    public function testSendRequestBase()
    {
        $payment = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $payment->method('getOperationMode')->willReturn('test');

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getCode')->willReturn(null);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRealOrderId', 'getOrderCurrencyCode', 'getStore'])
            ->getMock();
        $order->method('getRealOrderId')->willReturn('54321');
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getStore')->willReturn($store);

        $paymentInfo = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getParentTransactionId'])
            ->getMock();
        $paymentInfo->method('getOrder')->willReturn($order);
        $paymentInfo->method('getParentTransactionId')->willReturn('12345');

        $response = ['status' => 'APPROVED'];
        $this->apiHelper->method('sendApiRequest')->willReturn($response);

        $this->classToTest->addParameter('test', '', true);

        $resultMissing = $this->classToTest->getParameter('missing');
        $this->assertFalse($resultMissing);

        $this->classToTest->removeParameter('mid');

        $result = $this->classToTest->sendRequest($payment, $paymentInfo, 100);
        $this->assertArrayHasKey('errormessage', $result);
    }
}
