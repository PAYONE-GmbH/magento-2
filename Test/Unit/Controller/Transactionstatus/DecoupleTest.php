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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Controller\Transactionstatus;

use Payone\Core\Controller\Transactionstatus\Decouple as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\App\Request\Http;
use Payone\Core\Model\TransactionStatus\Forwarding;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

#[AllowMockObjectsWithoutExpectations]
class DecoupleTest extends BaseTestCase
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
     * @var Http
     */
    private $request;

    /**
     * @var Forwarding|\PHPUnit_Framework_MockObject_MockObject
     */
    private $statusForwarding;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam', 'setParam', 'getPostValue', 'isPost'])
            ->getMock();
        $this->request->method('getPostValue')->willReturn([
            'txaction' => 'paid',
            'key' => 'abc',
            Forwarding::DECOUPLE_SIGNATURE_PARAM => 'signature'
        ]);
        $this->request->method('isPost')->willReturn(true);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($this->request);

        $rawResponse = $this->getMockBuilder(Raw::class)->disableOriginalConstructor()->getMock();
        $resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $resultRawFactory->method('create')->willReturn($rawResponse);

        $this->statusForwarding = $this->getMockBuilder(Forwarding::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'resultRawFactory' => $resultRawFactory,
            'statusForwarding' => $this->statusForwarding
        ]);
    }

    public function testExecute()
    {
        $expectedStatus = ['txaction' => 'paid', 'key' => 'abc'];

        $this->statusForwarding->expects($this->once())
            ->method('isDecoupleSignatureValid')
            ->with($expectedStatus, 'signature')
            ->willReturn(true);
        $this->statusForwarding->expects($this->once())->method('handleForwardings')->with($expectedStatus);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    public function testExecuteInvalidSignature()
    {
        $this->statusForwarding->method('isDecoupleSignatureValid')->willReturn(false);
        $this->statusForwarding->expects($this->never())->method('handleForwardings');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }
}
