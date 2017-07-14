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
use Payone\Core\Model\PayoneConfig;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
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

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->payment = $this->objectManager->getObject(Payment::class, [
            'context' => $context,
            'storeManager' => $storeManager
        ]);
    }

    public function testGetAvailablePaymentTypes()
    {
        $result = $this->payment->getAvailablePaymentTypes();
        $this->assertContains(PayoneConfig::METHOD_CREDITCARD, $result);
    }

    public function testGetAvailableCreditcardTypes()
    {
        $creditcardTypes = 'V,M';
        $expected = [
            ['id' => 'V', 'title' => 'Visa'],
            ['id' => 'M', 'title' => 'Mastercard']
        ];
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/types', ScopeInterface::SCOPE_STORE, null, $creditcardTypes]]);

        $result = $this->payment->getAvailableCreditcardTypes();
        $this->assertEquals($expected, $result);
    }

    public function testIsCheckCvcActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/check_cvc', ScopeInterface::SCOPE_STORE, null, 1]]);
        $result = $this->payment->isCheckCvcActive();
        $this->assertTrue($result);
    }

    public function testIsMandateManagementActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/sepa_mandate_enabled', ScopeInterface::SCOPE_STORE, null, 1]]);
        $result = $this->payment->isMandateManagementActive();
        $this->assertTrue($result);
    }

    public function testIsMandateManagementDownloadActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/sepa_mandate_download_enabled', ScopeInterface::SCOPE_STORE, null, 1]]);
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
            ->willReturnMap([['payone_general/statusmapping/'.PayoneConfig::METHOD_CREDITCARD, ScopeInterface::SCOPE_STORE, null, serialize($mapping)]]);
        $result = $this->payment->getStatusMappingByCode(PayoneConfig::METHOD_CREDITCARD);
        $expected = ['appointed' => 'processing'];
        $this->assertEquals($expected, $result);
    }

    public function testGetBankaccountCheckBlockedMessage()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/message_response_blocked/', ScopeInterface::SCOPE_STORE, null, '']]);
        $result = $this->payment->getBankaccountCheckBlockedMessage();
        $expected = 'Bankdata invalid.';
        $this->assertEquals($expected, $result);
    }

    public function testIsPayPalExpressActive()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_PAYPAL.'/express_active', ScopeInterface::SCOPE_STORE, null, 1]]);
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
}
