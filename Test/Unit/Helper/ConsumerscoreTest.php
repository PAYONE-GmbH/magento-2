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

use Payone\Core\Helper\Consumerscore;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\Database;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ConsumerscoreTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Consumerscore
     */
    private $consumerscore;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var Database|\PHPUnit_Framework_MockObject_MockObject
     */
    private $databaseHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);
        $store->method('getId')->willReturn(5);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();

        $this->consumerscore = $this->objectManager->getObject(Consumerscore::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'databaseHelper' => $this->databaseHelper
        ]);
    }

    public function testGetConsumerscoreSampleCounterFilled()
    {
        $expected = 5;
        $this->databaseHelper->method('getConfigParamWithoutCache')->willReturn($expected);
        $result = $this->consumerscore->getConsumerscoreSampleCounter();
        $this->assertEquals($expected, $result);
    }

    public function testGetConsumerscoreSampleCounterNotFilled()
    {
        $expected = 0;
        $this->databaseHelper->method('getConfigParamWithoutCache')->willReturn(false);
        $result = $this->consumerscore->getConsumerscoreSampleCounter();
        $this->assertEquals($expected, $result);
    }

    public function testSetConsumerscoreSampleCounter()
    {
        $result = $this->consumerscore->setConsumerscoreSampleCounter(5);
        $expected = true;
        $this->assertEquals($expected, $result);
    }

    public function testIncrementConsumerscoreSampleCounter()
    {
        $this->databaseHelper->method('getConfigParamWithoutCache')->willReturn(5);
        $result = $this->consumerscore->incrementConsumerscoreSampleCounter();
        $expected = 6;
        $this->assertEquals($expected, $result);
    }

    public function testIsSampleNeeded()
    {
        $this->databaseHelper->method('getConfigParamWithoutCache')->willReturn(5);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/sample_mode_frequency', ScopeInterface::SCOPE_STORE, null, 5],
                    ['payone_protect/creditrating/sample_mode_enabled', ScopeInterface::SCOPE_STORE, null, 1]
                ]
            );
        $result = $this->consumerscore->isSampleNeeded();
        $this->assertTrue($result);
    }

    public function testIsSampleNotNeeded()
    {
        $this->databaseHelper->method('getConfigParamWithoutCache')->willReturn(3);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/sample_mode_frequency', ScopeInterface::SCOPE_STORE, null, 5],
                    ['payone_protect/creditrating/sample_mode_enabled', ScopeInterface::SCOPE_STORE, null, 1]
                ]
            );
        $result = $this->consumerscore->isSampleNeeded();
        $this->assertFalse($result);
    }

    public function testCanShowPaymentHintText()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/payment_hint_enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'after_payment']
                ]
            );
        $result = $this->consumerscore->canShowPaymentHintText();
        $this->assertTrue($result);
    }

    public function testMustNotShowPaymentHintText()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/payment_hint_enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'before_payment']
                ]
            );
        $result = $this->consumerscore->canShowPaymentHintText();
        $this->assertFalse($result);
    }

    public function testCanShowAgreementMessage()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/agreement_enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'after_payment']
                ]
            );
        $result = $this->consumerscore->canShowAgreementMessage();
        $this->assertTrue($result);
    }

    public function testMustNotShowAgreementMessage()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/agreement_enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'before_payment']
                ]
            );
        $result = $this->consumerscore->canShowAgreementMessage();
        $this->assertFalse($result);
    }

    /**
     * @return array
     */
    public function getScoreArrays()
    {
        return [
            [['Y', 'G', 'R'], 'R'],
            [['G', 'Y'], 'Y'],
            [['G'], 'G']
        ];
    }

    /**
     * @param array $scores
     * @param string $expected
     *
     * @dataProvider getScoreArrays
     */
    public function testGetWorstScore($scores, $expected)
    {
        $result = $this->consumerscore->getWorstScore($scores);
        $this->assertEquals($expected, $result);
    }

    public function testGetAllowedMethodsForScore()
    {
        $yellowMethods = 'payone_creditcard,payone_debit';
        $redMethods = 'payone_paypal,payone_invoice';
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/allow_payment_methods_yellow', ScopeInterface::SCOPE_STORE, null, $yellowMethods],
                    ['payone_protect/creditrating/allow_payment_methods_red', ScopeInterface::SCOPE_STORE, null, $redMethods]
                ]
            );
        $result = $this->consumerscore->getAllowedMethodsForScore('Y');
        $expected = explode(',', $yellowMethods);
        $this->assertEquals($expected, $result);

        $result = $this->consumerscore->getAllowedMethodsForScore('R');
        $expected = explode(',', $redMethods);
        $this->assertEquals($expected, $result);
    }

    public function testCopyOldStatusToNewAddress()
    {
        $expected = 'Y';

        $this->databaseHelper->method('getOldAddressStatus')->willReturn($expected);
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPayoneProtectScore', 'save', 'getPayoneProtectScore'])
            ->getMock();
        $address->method('setPayoneProtectScore')->willReturn($address);
        $address->method('save')->willReturn(true);
        $address->method('getPayoneProtectScore')->willReturn($expected);

        $this->consumerscore->copyOldStatusToNewAddress($address);
        $this->assertEquals($expected, $address->getPayoneProtectScore());
    }

    public function testIsCheckNeededForPrice()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/min_order_total', ScopeInterface::SCOPE_STORE, null, 10],
                    ['payone_protect/creditrating/max_order_total', ScopeInterface::SCOPE_STORE, null, 1000]
                ]
            );
        $result = $this->consumerscore->isCheckNeededForPrice(500);
        $this->assertTrue($result);

        $result = $this->consumerscore->isCheckNeededForPrice(5000);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeededNotEnabled()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 0]
                ]
            );
        $result = $this->consumerscore->isCreditratingNeeded('after_payment', 500);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeededWrongEvent()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'before_payment']
                ]
            );
        $result = $this->consumerscore->isCreditratingNeeded('after_payment', 500);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeededWrongPrice()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'before_payment'],
                    ['payone_protect/creditrating/min_order_total', ScopeInterface::SCOPE_STORE, null, 10],
                    ['payone_protect/creditrating/max_order_total', ScopeInterface::SCOPE_STORE, null, 1000]
                ]
            );
        $result = $this->consumerscore->isCreditratingNeeded('before_payment', 5000);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeededSampleNotNeeded()
    {
        $this->databaseHelper->method('getConfigParamWithoutCache')->willReturn(3);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'before_payment'],
                    ['payone_protect/creditrating/min_order_total', ScopeInterface::SCOPE_STORE, null, 10],
                    ['payone_protect/creditrating/max_order_total', ScopeInterface::SCOPE_STORE, null, 1000],
                    ['payone_protect/creditrating/sample_mode_frequency', ScopeInterface::SCOPE_STORE, null, 5],
                    ['payone_protect/creditrating/sample_mode_enabled', ScopeInterface::SCOPE_STORE, null, 1]
                ]
            );
        $result = $this->consumerscore->isCreditratingNeeded('before_payment', 500);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeeded()
    {
        $this->databaseHelper->method('getConfigParamWithoutCache')->willReturn(5);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_protect/creditrating/enabled', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_protect/creditrating/integration_event', ScopeInterface::SCOPE_STORE, null, 'before_payment'],
                    ['payone_protect/creditrating/min_order_total', ScopeInterface::SCOPE_STORE, null, 10],
                    ['payone_protect/creditrating/max_order_total', ScopeInterface::SCOPE_STORE, null, 1000],
                    ['payone_protect/creditrating/sample_mode_frequency', ScopeInterface::SCOPE_STORE, null, 5],
                    ['payone_protect/creditrating/sample_mode_enabled', ScopeInterface::SCOPE_STORE, null, 1]
                ]
            );
        $result = $this->consumerscore->isCreditratingNeeded('before_payment', 500);
        $this->assertTrue($result);
    }
}
