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

namespace Payone\Core\Test\Unit\Controller\Mandate;

use Payone\Core\Controller\Transactionstatus\Index as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Helper\Environment;
use Payone\Core\Helper\Order;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\Order as OrderCore;
use Magento\Framework\Event\ManagerInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class IndexTest extends BaseTestCase
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
     * @var Toolkit|\PHPUnit_Framework_MockObject_MockObject
     */
    private $toolkitHelper;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $post = $this->getMockBuilder(self::class)->disableOriginalConstructor()->setMethods(['toArray'])->getMock();
        $post->method('toArray')->willReturn(['test' => 'array']);

        $request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam', 'getPost'])
            ->getMock();
        $request->method('getParam')->willReturn('Value');
        $request->method('getPost')->willReturn($post);

        $eventManater = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($request);
        $context->method('getEventManager')->willReturn($eventManater);

        $this->toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $this->environmentHelper = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();

        $order = $this->getMockBuilder(OrderCore::class)->disableOriginalConstructor()->getMock();
        $orderHelper = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $orderHelper->method('getOrderByTxid')->willReturn($order);

        $rawResponse = $this->getMockBuilder(Raw::class)->disableOriginalConstructor()->getMock();
        $resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $resultRawFactory->method('create')->willReturn($rawResponse);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'toolkitHelper' => $this->toolkitHelper,
            'environmentHelper' => $this->environmentHelper,
            'orderHelper' => $orderHelper,
            'resultRawFactory' => $resultRawFactory
        ]);
    }

    public function testExecuteIpInvalid()
    {
        $this->environmentHelper->method('isRemoteIpValid')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    public function testExecuteKeyInvalid()
    {
        $this->environmentHelper->method('isRemoteIpValid')->willReturn(true);
        $this->toolkitHelper->method('isKeyValid')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    public function testExecute()
    {
        $this->environmentHelper->method('isRemoteIpValid')->willReturn(true);
        $this->toolkitHelper->method('isKeyValid')->willReturn(true);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }
}
