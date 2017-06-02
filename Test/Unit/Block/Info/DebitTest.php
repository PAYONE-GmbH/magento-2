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

namespace Payone\Core\Test\Unit\Block\Info;

use Magento\Sales\Model\Order;
use Payone\Core\Block\Info\Debit as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Info;
use Payone\Core\Model\Entities\TransactionStatus;
use Payone\Core\Model\TransactionStatusRepository;

class DebitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Info|\PHPUnit_Framework_MockObject_MockObject
     */
    private $info;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getPayoneTxid')->willReturn('12345');

        $this->info = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLastTransId', 'getOrder'])
            ->getMock();
        $this->info->method('getOrder')->willReturn($order);

        $rawStatus = [
            'iban' => '12345',
            'bic' => '12345',
            'bankaccount' => '12345',
            'bankcode' => '12345'
        ];

        $transactionStatus = $this->getMockBuilder(TransactionStatus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionStatus->method('getRawStatusArray')->willReturn($rawStatus);

        $transactionStatusRepository = $this->getMockBuilder(TransactionStatusRepository::class)->disableOriginalConstructor()->getMock();
        $transactionStatusRepository->method('getAppointedByTxid')->willReturn($transactionStatus);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'transactionStatusRepository' => $transactionStatusRepository
        ]);
        $this->classToTest->setInfo($this->info);
    }

    public function testPrepareSpecificInformation()
    {
        $this->info->method('getLastTransId')->willReturn('12345');

        $result = $this->classToTest->getSpecificInformation();
        $this->assertArrayHasKey('IBAN:', $result);

        $result = $this->classToTest->getSpecificInformation();
        $this->assertNotEmpty($result);
    }

    public function testPrepareSpecificInformationNoLastTransId()
    {
        $this->info->method('getLastTransId')->willReturn('');

        $result = $this->classToTest->getSpecificInformation();
        $this->assertArrayHasKey('Payment has not been processed yet.', $result);
    }
}
