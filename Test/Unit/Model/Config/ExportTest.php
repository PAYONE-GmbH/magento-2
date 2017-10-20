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

namespace Payone\Core\Test\Unit\Model\Config;

use Payone\Core\Model\Config\Export as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\ConfigExport;
use Payone\Core\Model\ChecksumCheck;
use Magento\Store\Model\StoreManagerInterface;
use Payone\Core\Helper\Payment;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Risk\Addresscheck;
use Magento\Store\Api\Data\StoreInterface;
use Payone\Core\Helper\Shop;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;


class ExportTest extends BaseTestCase
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
     * @var ChecksumCheck|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checksumCheck;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $configExportHelper = $this->getMockBuilder(ConfigExport::class)->disableOriginalConstructor()->getMock();
        $configExportHelper->method('getModuleInfo')->willReturn(['Test_Module' => '1.2.3']);
        $configExportHelper->method('getConfigParam')->willReturn('value');
        $configExportHelper->method('getMappings')->willReturn([
            'cc' => [
                ['from' => 'appointed', 'to' => 'pending']
            ]
        ]);
        $configExportHelper->method('getPaymentConfig')->willReturn('value');
        $configExportHelper->method('getCountries')->willReturn('DE,AT');
        $configExportHelper->method('getForwardings')->willReturn([
            ['status' => 'appointed', 'url' => 'http://testdomain.org', 'timeout' => '45']
        ]);

        $this->checksumCheck = $this->getMockBuilder(ChecksumCheck::class)->disableOriginalConstructor()->getMock();

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getName')->willReturn('Testshop');
        $stores = ['1' => $store];
        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStores')->willReturn($stores);

        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('getAvailablePaymentTypes')->willReturn([PayoneConfig::METHOD_CREDITCARD]);
        $paymentHelper->method('getPaymentAbbreviation')->willReturn('cc');

        $addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $addresscheck->method('getPersonstatusMapping')->willReturn(['ABC' => 'G', 'NONE' => 'R']);

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getMagentoVersion')->willReturn('2.0.0');
        $shopHelper->method('getMagentoEdition')->willReturn('CE');
        $shopHelper->method('getConfigParam')->willReturn('value');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $shopHelper,
            'configExportHelper' => $configExportHelper,
            'checksumCheck' => $this->checksumCheck,
            'storeManager' => $storeManager,
            'paymentHelper' => $paymentHelper,
            'addresscheck' => $addresscheck
        ]);
    }

    public function testHandleForwardings()
    {
        $this->checksumCheck->method('getChecksumErrors')->willReturn(false);

        $result = $this->classToTest->generateConfigExportXml();
        $this->assertNotEmpty($result);
    }

    public function testHandleForwardingsChecksumError()
    {
        $this->checksumCheck->method('getChecksumErrors')->willReturn(['Something is wrong']);

        $result = $this->classToTest->generateConfigExportXml();
        $this->assertNotEmpty($result);
    }
}
