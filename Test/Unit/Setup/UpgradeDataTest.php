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

namespace Payone\Core\Test\Unit\Setup;

use Payone\Core\Setup\UpgradeData as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Sales\Setup\SalesSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Shop;
use Payone\Core\Helper\Payment;
use Magento\Framework\Serialize\Serializer\Serialize;

class UpgradeDataTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $salesSetup = $this->getMockBuilder(SalesSetup::class)->disableOriginalConstructor()->getMock();
        $salesSetupFactory = $this->getMockBuilder(SalesSetupFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $salesSetupFactory->method('create')->willReturn($salesSetup);

        $customerSetup = $this->getMockBuilder(CustomerSetup::class)->disableOriginalConstructor()->getMock();
        $customerSetup->method('getAttribute')->willReturn(false);

        $customerSetupFactory = $this->getMockBuilder(CustomerSetupFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $customerSetupFactory->method('create')->willReturn($customerSetup);

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getMagentoVersion')->willReturn('2.2.0');

        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('getAvailablePaymentTypes')->willReturn(['payone_paypal']);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'salesSetupFactory' => $salesSetupFactory,
            'customerSetupFactory' => $customerSetupFactory,
            'shopHelper' => $shopHelper,
            'paymentHelper' => $paymentHelper,
        ]);
    }

    public function testInstall()
    {
        $oSerialize = $this->objectManager->getObject(Serialize::class);

        $fetchResult = [['value' => $oSerialize->serialize(['a' => 'b']), 'config_id' => 12]];

        $connection = $this->getMockBuilder(Mysql::class)
            ->setMethods(['tableColumnExists', 'select', 'from', 'where', 'fetchAssoc', 'update'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection->method('tableColumnExists')->willReturn(false);
        $connection->method('select')->willReturn($connection);
        $connection->method('from')->willReturn($connection);
        $connection->method('where')->willReturn($connection);
        $connection->method('update')->willReturn(1);
        $connection->method('fetchAssoc')->willReturn($fetchResult);

        $setup = $this->getMockBuilder(ModuleDataSetupInterface::class)->disableOriginalConstructor()->getMock();
        $setup->method('getTable')->willReturn('table');
        $setup->method('getConnection')->willReturn($connection);

        $context = $this->getMockBuilder(ModuleContextInterface::class)->disableOriginalConstructor()->getMock();
        $context->method('getVersion')->willReturn('2.0.1');

        $result = $this->classToTest->upgrade($setup, $context);
        $this->assertNull($result);
    }

    public function testInstallOldPayment()
    {
        $oSerialize = $this->objectManager->getObject(Serialize::class);

        $fetchResult = [['value' => $oSerialize->serialize(['a' => 'b']), 'config_id' => 12]];

        $connection = $this->getMockBuilder(Mysql::class)
            ->setMethods(['tableColumnExists', 'select', 'from', 'where', 'fetchAssoc', 'update'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection->method('tableColumnExists')->willReturn(false);
        $connection->method('select')->willReturn($connection);
        $connection->method('from')->willReturn($connection);
        $connection->method('where')->willReturn($connection);
        $connection->method('update')->willReturn(1);
        $connection->method('fetchAssoc')->willReturn($fetchResult);

        $setup = $this->getMockBuilder(ModuleDataSetupInterface::class)->disableOriginalConstructor()->getMock();
        $setup->method('getTable')->willReturn('table');
        $setup->method('getConnection')->willReturn($connection);

        $context = $this->getMockBuilder(ModuleContextInterface::class)->disableOriginalConstructor()->getMock();
        $context->method('getVersion')->willReturn('2.0.1');

        $result = $this->classToTest->upgrade($setup, $context);
        $this->assertNull($result);
    }
}
