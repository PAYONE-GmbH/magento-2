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
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\App\Request\Http;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

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

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $post = $this->getMockBuilder(self::class)->disableOriginalConstructor()->addMethods(['toArray'])->getMock();
        $post->method('toArray')->willReturn(['test' => 'array']);

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam', 'setParam', 'getPost', 'isPost'])
            ->getMock();
        $this->request->method('getPost')->willReturn($post);
        $this->request->method('isPost')->willReturn(true);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($this->request);

        $rawResponse = $this->getMockBuilder(Raw::class)->disableOriginalConstructor()->getMock();
        $resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $resultRawFactory->method('create')->willReturn($rawResponse);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'resultRawFactory' => $resultRawFactory
        ]);
    }

    public function testExecute()
    {
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }
}
