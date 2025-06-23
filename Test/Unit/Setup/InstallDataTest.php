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

use Payone\Core\Setup\InstallData as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Sales\Setup\SalesSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\DB\Adapter\Pdo\Mysql;

class InstallDataTest extends BaseTestCase
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
            ->onlyMethods(['create'])
            ->getMock();
        $salesSetupFactory->method('create')->willReturn($salesSetup);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'salesSetupFactory' => $salesSetupFactory
        ]);
    }

    public function testInstall()
    {
        $fetchResult = [['scope' => 'website', 'scope_id' => 2]];

        $connection = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(['select', 'insert', 'fetchAssoc'])
            ->addMethods(['from', 'where', 'order'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection->method('select')->willReturn($connection);
        $connection->method('from')->willReturn($connection);
        $connection->method('where')->willReturn($connection);
        $connection->method('order')->willReturn($connection);
        $connection->method('insert')->willReturn(1);
        $connection->method('fetchAssoc')->willReturn($fetchResult);

        $setup = $this->getMockBuilder(ModuleDataSetupInterface::class)->disableOriginalConstructor()->getMock();
        $setup->method('getTable')->willReturn('table');
        $setup->method('getConnection')->willReturn($connection);

        $context = $this->getMockBuilder(ModuleContextInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->install($setup, $context);
        $this->assertNull($result);
    }
}
