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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Handler;

use Magento\Sales\Model\Order;
use Payone\Core\Model\Handler\TransactionStatus as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class TransactionStatusTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);
    }

    public function testHandle()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $status = ['txaction' => 'appointed'];

        $result = $this->classToTest->handle($order, $status);
        $this->assertNull($result);
    }

    public function testHandleGetParamNull()
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $status = [];

        $result = $this->classToTest->handle($order, $status);
        $this->assertNull($result);
    }
}
