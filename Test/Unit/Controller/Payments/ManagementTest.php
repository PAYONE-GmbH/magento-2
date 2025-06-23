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

namespace Payone\Core\Test\Unit\Controller\Payments;

use Payone\Core\Controller\Payments\Management as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use \Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\Context;
use Payone\Core\Helper\Base;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;

class ManagementTest extends BaseTestCase
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
     * @var Base
     */
    private $baseHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $title = $this->getMockBuilder(Title::class)->disableOriginalConstructor()->getMock();
        $title->method('set')->willReturn($title);

        $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $config->method('getTitle')->willReturn($title);

        $page = $this->getMockBuilder(Page::class)->disableOriginalConstructor()->getMock();
        $page->method('getConfig')->willReturn($config);

        $pageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $pageFactory->method('create')->willReturn($page);

        $resultRedirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $resultRedirect->method('setPath')->willReturn($resultRedirect);
        $resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $resultRedirectFactory->method('create')->willReturn($resultRedirect);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResultRedirectFactory')->willReturn($resultRedirectFactory);

        $this->baseHelper = $this->getMockBuilder(Base::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'pageFactory' => $pageFactory,
            'baseHelper' => $this->baseHelper
        ]);
    }

    public function testExecute()
    {
        $this->baseHelper->method('getConfigParam')->willReturn('1');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    public function testExecuteDisabled()
    {
        $this->baseHelper->method('getConfigParam')->willReturn('0');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }
}
