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

namespace Payone\Core\Test\Unit\Model\Plugins;

use Payone\Core\Model\Plugins\CreditmemoService as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Api\Data\CreditmemoInterface;

class CreditmemoServiceTest extends BaseTestCase
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
     * @var Session
     */
    private $checkoutSession;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPayoneDebitRequest',
                'getPayoneDebitResponse',
                'getPayoneDebitOrderId',
                'unsPayoneDebitRequest',
                'unsPayoneDebitResponse',
                'unsPayoneDebitOrderId'
            ])
            ->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $this->checkoutSession,
        ]);
    }

    public function testAroundRefund()
    {
        $creditmemo = $this->getMockBuilder(CreditmemoInterface::class)->disableOriginalConstructor()->getMock();

        $subject = $this->getMockBuilder(CreditmemoService::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($creditmemo) {
            return $creditmemo;
        };

        #$this->expectException(CouldNotSaveException::class);
        $result = $this->classToTest->aroundRefund($subject, $proceed, $creditmemo, false);

        $this->assertEquals($creditmemo, $result);
    }

    public function testAroundRefundException()
    {
        $creditmemo = $this->getMockBuilder(CreditmemoInterface::class)->disableOriginalConstructor()->getMock();

        $subject = $this->getMockBuilder(CreditmemoService::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($creditmemo) {
            throw new \Exception('Test');
        };

        $this->checkoutSession->method('getPayoneDebitRequest')->willReturn(['request' => 'debit']);

        $this->expectException(\Exception::class);
        $this->classToTest->aroundRefund($subject, $proceed, $creditmemo, false);
    }
}
