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

use Payone\Core\Helper\Payment;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class PaymentTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var Toolkit
     */
    private $toolkitHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getMagentoVersion')->willReturn("2.4.4");

        $this->toolkitHelper = $this->objectManager->getObject(Toolkit::class, [
            'shopHelper' => $shopHelper
        ]);

        $this->payment = $this->objectManager->getObject(Payment::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'toolkitHelper' => $this->toolkitHelper
        ]);
    }

    public function testGetAvailablePaymentTypes()
    {
        $result = $this->payment->getAvailablePaymentTypes();
        $this->assertContains(PayoneConfig::METHOD_CREDITCARD, $result);
    }

    public function testGetAvailableCreditcardTypes()
    {
        $creditcardTypes = 'visa,mastercard';
        $expected = [
            ['id' => 'V', 'title' => 'Visa', 'cvc_length' => 3],
            ['id' => 'M', 'title' => 'Mastercard', 'cvc_length' => 3]
        ];
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/types', ScopeInterface::SCOPE_STORES, null, $creditcardTypes]]);

        $result = $this->payment->getAvailableCreditcardTypes();
        $this->assertEquals($expected, $result);
    }

    public function testIsCheckCvcActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/check_cvc', ScopeInterface::SCOPE_STORES, null, 1]]);
        $result = $this->payment->isCheckCvcActive();
        $this->assertTrue($result);
    }

    public function testIsMandateManagementActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/sepa_mandate_enabled', ScopeInterface::SCOPE_STORES, null, 1]]);
        $result = $this->payment->isMandateManagementActive();
        $this->assertTrue($result);
    }

    public function testIsMandateManagementDownloadActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/sepa_mandate_download_enabled', ScopeInterface::SCOPE_STORES, null, 1]]);
        $result = $this->payment->isMandateManagementDownloadActive();
        $this->assertTrue($result);
    }

    public function testGetStatusMappingByCode()
    {
        $mapping = [
            'key' => [
                'txaction' => 'appointed',
                'state_status' => 'processing'
            ]
        ];
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_general/statusmapping/'.PayoneConfig::METHOD_CREDITCARD, ScopeInterface::SCOPE_STORES, null, $this->toolkitHelper->serialize($mapping)]]);
        $result = $this->payment->getStatusMappingByCode(PayoneConfig::METHOD_CREDITCARD);
        $expected = ['appointed' => 'processing'];
        $this->assertEquals($expected, $result);
    }

    public function testGetBankaccountCheckBlockedMessage()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/message_response_blocked/', ScopeInterface::SCOPE_STORES, null, '']]);
        $result = $this->payment->getBankaccountCheckBlockedMessage();
        $expected = 'Bankdata invalid.';
        $this->assertEquals($expected, $result);
    }

    public function testIsPayPalExpressActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_PAYPAL.'/express_active', ScopeInterface::SCOPE_STORES, null, 1]]);
        $result = $this->payment->isPayPalExpressActive();
        $this->assertTrue($result);
    }

    public function testGetPaymentAbbreviation()
    {
        $result = $this->payment->getPaymentAbbreviation(PayoneConfig::METHOD_CREDITCARD);
        $expected = 'cc';
        $this->assertEquals($expected, $result);

        $result = $this->payment->getPaymentAbbreviation('not_existing');
        $expected = 'unknown';
        $this->assertEquals($expected, $result);
    }

    public function testGetKlarnaStoreIds()
    {
        $storeIds = ['key' => ['store_id' => '123', 'countries' => ['DE', 'AT']]];

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_KLARNA.'/klarna_config', ScopeInterface::SCOPE_STORES, null, $this->toolkitHelper->serialize($storeIds)]]);

        $expected = ['DE' => '123', 'AT' => '123'];
        $result = $this->payment->getKlarnaStoreIds();
        $this->assertEquals($expected, $result);
    }

    public function testGetKlarnaStoreIdsEmpty()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_KLARNA.'/klarna_config', ScopeInterface::SCOPE_STORES, null, $this->toolkitHelper->serialize([])]]);

        $expected = [];
        $result = $this->payment->getKlarnaStoreIds();
        $this->assertEquals($expected, $result);
    }

    public function testIsPaymentMethodActive()
    {
        $code = 'payone_creditcard';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payment/'.$code.'/active', ScopeInterface::SCOPE_STORES, null, true]]);

        $result = $this->payment->isPaymentMethodActive($code);
        $this->assertTrue($result);
    }

    public function testGetAmazonPayWidgetUrl()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_AMAZONPAY.'/mode', ScopeInterface::SCOPE_STORES, null, 'test']]);

        $result = $this->payment->getAmazonPayWidgetUrl();
        $this->assertNotEmpty($result);
    }

    public function testGetKlarnaMethodTitles()
    {
        $result = $this->payment->getKlarnaMethodTitles();
        $this->assertCount(3, $result);
    }

    public function testGetAvailableApplePayTypes()
    {
        $types = 'visa,mastercard';
        $this->scopeConfig->method('getValue')->willReturn($types);


        $result = $this->payment->getAvailableApplePayTypes();
        $this->assertCount(2, $result);
    }

    public function testGetAvailableApplePayTypesEmpty()
    {
        $this->scopeConfig->method('getValue')->willReturn(null);


        $result = $this->payment->getAvailableApplePayTypes();
        $this->assertCount(0, $result);
    }
}
