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

namespace Payone\Core\Test\Unit\Controller\Adminhtml\Config\Export;

use Payone\Core\Controller\Adminhtml\Config\Export\Index as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\AuthorizationInterface;
use Payone\Core\Model\Config\Export;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

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
     * @var Export|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configExport;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $authorization = $this->getMockBuilder(AuthorizationInterface::class)->disableOriginalConstructor()->getMock();
        $authorization->method('isAllowed')->willReturn(true);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getAuthorization')->willReturn($authorization);

        $this->configExport = $this->getMockBuilder(Export::class)->disableOriginalConstructor()->getMock();

        $raw = $this->getMockBuilder(Raw::class)->disableOriginalConstructor()->getMock();

        $resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $resultRawFactory->method('create')->willReturn($raw);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'configExport' => $this->configExport,
            'resultRawFactory' => $resultRawFactory
        ]);
    }

    public function testExecute()
    {
        $this->configExport->method('generateConfigExportXml')->willReturn('export xml');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    public function testExecuteException()
    {
        $exception = new \Exception();
        $this->configExport->method('generateConfigExportXml')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }
}
