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

use Payone\Core\Helper\ConfigExport;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\Database;
use Payone\Core\Helper\Config;
use Payone\Core\Helper\Payment;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ConfigExportTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var ConfigExport
     */
    private $configExport;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var Toolkit
     */
    private $toolkitHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $databaseHelper->method('getModuleInfo')->willReturn([
            ['module' => 'Payone_Core', 'schema_version' => '1.3.1'],
            ['module' => 'Another_Module', 'schema_version' => '2.3.4']
        ]);

        $configHelper = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $configHelper->method('getForwardingUrls')->willReturn([
            ['txaction' => ['appointed', 'pending'], 'url' => 'http://forward.to', 'timeout' => 15],
            ['txaction' => ['paid'], 'url' => 'http://forward.to', 'timeout' => null]
        ]);

        $paymentHelper = $this->objectManager->getObject(Payment::class);

        $this->toolkitHelper = $this->objectManager->getObject(Toolkit::class);

        $this->configExport = $this->objectManager->getObject(ConfigExport::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'paymentHelper' => $paymentHelper,
            'databaseHelper' => $databaseHelper,
            'configHelper' => $configHelper,
            'toolkitHelper' => $this->toolkitHelper
        ]);
    }

    public function testGetModuleInfo()
    {
        $result = $this->configExport->getModuleInfo();
        $expected = [
            'Payone_Core' => '1.3.1',
            'Another_Module' => '2.3.4'
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetPaymentConfigNonGlobal()
    {
        $expected = '12345';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_payment/payone_creditcard/use_global', ScopeInterface::SCOPE_STORE, null, 0],
                    ['payone_payment/payone_creditcard/mid', ScopeInterface::SCOPE_STORE, null, $expected]
                ]
            );
        $result = $this->configExport->getPaymentConfig('mid', 'payone_creditcard', null, false);
        $this->assertEquals($expected, $result);
        $result = $this->configExport->getPaymentConfig('mid', 'payone_creditcard', null, true);
        $this->assertEquals($expected, $result);
    }

    public function testGetPaymentConfigGlobal()
    {
        $expected = '12345';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_payment/payone_creditcard/use_global', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_payment/payone_creditcard/mid', ScopeInterface::SCOPE_STORE, null, 'random_mid'],
                    ['payone_general/global/mid', ScopeInterface::SCOPE_STORE, null, $expected]
                ]
            );
        $result = $this->configExport->getPaymentConfig('mid', 'payone_creditcard', null, true);
        $this->assertEquals($expected, $result);
    }

    public function testGetCountries()
    {
        $expected = 'DE,FR,NL';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_payment/payone_creditcard/use_global', ScopeInterface::SCOPE_STORE, null, 0],
                    ['payone_payment/payone_creditcard/allowspecific', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_payment/payone_creditcard/specificcountry', ScopeInterface::SCOPE_STORE, null, $expected],
                    ['payone_payment/payone_invoice/use_global', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_general/global/allowspecific', ScopeInterface::SCOPE_STORE, null, 1],
                    ['payone_general/global/specificcountry', ScopeInterface::SCOPE_STORE, null, $expected],
                    ['payone_payment/payone_paypal/use_global', ScopeInterface::SCOPE_STORE, null, 0],
                    ['payone_general/payone_paypal/allowspecific', ScopeInterface::SCOPE_STORE, null, 0]
                ]
            );

        $result = $this->configExport->getCountries('payone_creditcard', null);
        $this->assertEquals($expected, $result);

        $result = $this->configExport->getCountries('payone_invoice', null);
        $this->assertEquals($expected, $result);

        $result = $this->configExport->getCountries('payone_paypal', null);
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    public function testGetForwardings()
    {
        $result = $this->configExport->getForwardings(null);
        $expected = [
            ['status' => 'appointed,pending', 'url' => 'http://forward.to', 'timeout' => 15],
            ['status' => 'paid', 'url' => 'http://forward.to', 'timeout' => 0],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetMappings()
    {
        $config = ['random' => ['txaction' => 'appointed', 'state_status' => 'processing']];
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/statusmapping/payone_creditcard', ScopeInterface::SCOPE_STORE, null, $this->toolkitHelper->serialize($config)]
                ]
            );

        $result = $this->configExport->getMappings(null);
        $expected = ['cc' => [['from' => 'appointed', 'to' => 'processing']]];
        $this->assertEquals($result, $expected);
    }
}
