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

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\Toolkit;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\Payment;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Framework\DataObject;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ToolkitTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Toolkit
     */
    private $toolkit;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);
        $storeManager->method('getStores')->willReturn(['de' => $store, 'en' => $store, 'fr' => $store, 'nl' => $store]);

        $paymentHelper = $this->objectManager->getObject(Payment::class);
        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->toolkit = $this->objectManager->getObject(Toolkit::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'paymentHelper' => $paymentHelper,
            'shopHelper' => $this->shopHelper
        ]);
    }

    public function testGetAllPayoneSecurityKeys()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'de', '12345'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'en', '23456'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'fr', '12345'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'nl', '34567'],
                    ['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/use_global', ScopeInterface::SCOPE_STORE, 'nl', '0'],
                    ['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/key', ScopeInterface::SCOPE_STORE, 'nl', 'extra_payment_key'],
                ]
            );
        $result = $this->toolkit->getAllPayoneSecurityKeys();
        $expected = ['12345', '23456', '34567', 'extra_payment_key'];
        $this->assertEquals($expected, $result);
    }

    public function testIsKeyValid()
    {
        $key = 'extra_payment_key';
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'de', '12345'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'en', '23456'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'fr', '12345'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, 'nl', '34567'],
                    ['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/use_global', ScopeInterface::SCOPE_STORE, 'nl', '0'],
                    ['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/key', ScopeInterface::SCOPE_STORE, 'nl', $key],
                ]
            );

        $hash = md5($key);
        $result = $this->toolkit->isKeyValid($hash);
        $this->assertTrue($result);

        $result = $this->toolkit->isKeyValid('no hash');
        $this->assertFalse($result);
    }

    public function testHandleSubstituteReplacement()
    {
        $text = 'Lets pass this {{what}}';

        $result = $this->toolkit->handleSubstituteReplacement($text, ['{{what}}' => 'test']);
        $expected = 'Lets pass this test';
        $this->assertEquals($expected, $result);

        $result = $this->toolkit->handleSubstituteReplacement($text, ['{{what}}' => 'test'], 4);
        $expected = 'Lets';
        $this->assertEquals($expected, $result);
    }

    public function testHandleSubstituteReplacementEmpty()
    {
        $result = $this->toolkit->handleSubstituteReplacement('', ['{{replace_with}}' => 'something_different']);
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    public function testGetInvoiceAppendix()
    {
        $text = 'New order with order-nr {{order_increment_id}}. Your customer-id is {{customer_id}}';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_general/invoicing/invoice_appendix', ScopeInterface::SCOPE_STORE, null, $text]]);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getIncrementId')->willReturn('0000000001');
        $order->method('getCustomerId')->willReturn('123');

        $result = $this->toolkit->getInvoiceAppendix($order);
        $expected = 'New order with order-nr 0000000001. Your customer-id is 123';
        $this->assertEquals($expected, $result);
    }

    public function testGetNarrativeText()
    {
        $text = '{{order_increment_id}} was replaced. You cant read this';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/narrative_text', ScopeInterface::SCOPE_STORE, null, $text]]);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getIncrementId')->willReturn('0000000001');

        $payment = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);
        $payment->method('getNarrativeTextMaxLength')->willReturn(24);

        $result = $this->toolkit->getNarrativeText($order, $payment);
        $expected = '0000000001 was replaced.';
        $this->assertEquals($expected, $result);
    }

    public function testFormatNumber()
    {
        $result = $this->toolkit->formatNumber(192.20587);
        $expected = '192.21';
        $this->assertEquals($expected, $result);

        $result = $this->toolkit->formatNumber(192.20587, 8);
        $expected = '192.20587000';
        $this->assertEquals($expected, $result);
    }

    public function testIsUTF8()
    {
        $input = 'not utf-8 - ä';
        $result = $this->toolkit->isUTF8(utf8_decode($input));
        $this->assertFalse($result);

        $input = 'utf-8 äöü';
        $result = $this->toolkit->isUTF8(utf8_encode($input));
        $this->assertTrue($result);
    }

    public function testGetAdditionalDataEntryOld()
    {
        $expected = 'value';

        $dataObject = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();
        $dataObject->method('getData')->willReturn($expected);

        $this->shopHelper->method('getMagentoVersion')->willReturn('2.0.0');

        $result = $this->toolkit->getAdditionalDataEntry($dataObject, 'key');
        $this->assertEquals($expected, $result);
    }

    public function testGetAdditionalDataEntryNew()
    {
        $expected = 'value';

        $dataObject = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalData'])
            ->getMock();
        $dataObject->method('getAdditionalData')->willReturn(['key' => $expected]);

        $this->shopHelper->method('getMagentoVersion')->willReturn('2.1.3');

        $result = $this->toolkit->getAdditionalDataEntry($dataObject, 'key');
        $this->assertEquals($expected, $result);

        $result = $this->toolkit->getAdditionalDataEntry($dataObject, 'key2');
        $this->assertNull($result);
    }
}
