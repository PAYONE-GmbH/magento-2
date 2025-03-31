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
 * @copyright 2003 - 2025 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Methods;

use Magento\Checkout\Model\Session;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Payment\Model\Info;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\DataObject;
use Payone\Core\Helper\Shop;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Model\Methods\GooglePay as ClassToTest;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class GooglePayTest extends BaseTestCase
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
     * @var Shop
     */
    private $shopHelper;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var Quote
     */
    private $quote;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $this->store->method('getCode')->willReturn('test');

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getStore')->willReturn($this->store);

        $this->quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $info = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalInformation', 'getOrder'])
            ->getMock();
        $info->method('getAdditionalInformation')->willReturn('info');
        $info->method('getOrder')->willReturn($order);

        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('getAdditionalDataEntry')->willReturn('info');

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneMandate', 'unsPayoneMandate', 'getQuote'])
            ->getMock();
        $checkoutSession->method('getPayoneMandate')->willReturn(['mandate_identification' => '123', 'mandate_status' => 'pending']);
        $checkoutSession->method('getQuote')->willReturn($this->quote);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'toolkitHelper' => $toolkitHelper,
            'checkoutSession' => $checkoutSession,
            'shopHelper' => $this->shopHelper,
        ]);
        $this->classToTest->setInfoInstance($info);
    }

    public function testGetPaymentSpecificParameters()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getPaymentSpecificParameters($order);
        $expected = [
            'wallettype' => 'GGP',
            'api_version' => '3.11',
            'add_paydata[paymentmethod_token_data]' => base64_encode('info'),
        ];
        $this->assertEquals($expected, $result);
    }

    public function testAssignData()
    {
        $data = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->assignData($data);
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testGetFrontendConfig()
    {
        $expectedConfigParam = "testValue";

        $this->quote->method('getStore')->willReturn($this->store);
        $this->shopHelper->method('getConfigParam')->willReturn($expectedConfigParam);

        $expected = [
            'merchantId' => $expectedConfigParam,
            'storeName' => $expectedConfigParam,
            'operationMode' => $expectedConfigParam,
        ];

        $result = $this->classToTest->getFrontendConfig();
        $this->assertEquals($expected, $result);
    }

    public function testGetFrontendConfigNoStoreConfig()
    {
        $expectedConfigParam = "testValue";
        $expectedStoreName = "storeFrontendName";

        $this->quote->method('getStore')->willReturn($this->store);
        $this->store->method('getFrontendName')->willReturn($expectedStoreName);

        $this->shopHelper->method('getConfigParam')->willReturnMap(
            [
                ['mid', PayoneConfig::METHOD_GOOGLE_PAY, 'payone_payment', 'test', $expectedConfigParam],
                ['store_name', PayoneConfig::METHOD_GOOGLE_PAY, 'payment', 'test', null],
                ['mode', PayoneConfig::METHOD_GOOGLE_PAY, 'payone_payment', 'test', $expectedConfigParam],
            ]
        );

        $expected = [
            'merchantId' => $expectedConfigParam,
            'storeName' => $expectedStoreName,
            'operationMode' => $expectedConfigParam,
        ];

        $result = $this->classToTest->getFrontendConfig();
        $this->assertEquals($expected, $result);
    }

    public function testGetFrontendConfigStoreDoubleEmpty()
    {
        $expectedConfigParam = "testValue";

        $this->quote->method('getStore')->willReturn(null);

        $this->shopHelper->method('getConfigParam')->willReturnMap(
            [
                ['mid', PayoneConfig::METHOD_GOOGLE_PAY, 'payone_payment', 'test', $expectedConfigParam],
                ['store_name', PayoneConfig::METHOD_GOOGLE_PAY, 'payment', 'test', null],
                ['mode', PayoneConfig::METHOD_GOOGLE_PAY, 'payone_payment', 'test', $expectedConfigParam],
            ]
        );

        $expected = [
            'merchantId' => $expectedConfigParam,
            'storeName' => 'Online Store',
            'operationMode' => $expectedConfigParam,
        ];

        $result = $this->classToTest->getFrontendConfig();
        $this->assertEquals($expected, $result);
    }
}
