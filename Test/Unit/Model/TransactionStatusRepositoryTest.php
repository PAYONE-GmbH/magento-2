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

namespace Payone\Core\Test\Unit\Model\Methods;

use Payone\Core\Model\TransactionStatusRepository as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Entities\TransactionStatus;
use Payone\Core\Model\Entities\TransactionStatusFactory;
use Payone\Core\Model\ResourceModel\TransactionStatus as ResourceModel;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class TransactionStatusRepositoryTest extends BaseTestCase
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

        $resourceModel = $this->getMockBuilder(ResourceModel::class)->disableOriginalConstructor()->getMock();
        $resourceModel->method('getAppointedIdByTxid')->willReturn('12345');

        $transactionStatus = $this->getMockBuilder(TransactionStatus::class)->disableOriginalConstructor()->getMock();
        $transactionStatus->method('load')->willReturn($transactionStatus);

        $transactionStatusFactory = $this->getMockBuilder(TransactionStatusFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $transactionStatusFactory->method('create')->willReturn($transactionStatus);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'resourceModel' => $resourceModel,
            'transactionStatusFactory' => $transactionStatusFactory
        ]);
    }

    public function testGetAppointedByTxid()
    {
        $result = $this->classToTest->getAppointedByTxid('12345');
        $this->assertInstanceOf(TransactionStatus::class, $result);
    }
}
