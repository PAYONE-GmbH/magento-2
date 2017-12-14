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

namespace Payone\Core\Test\Unit\Controller\Adminhtml\Information;

use Payone\Core\Controller\Adminhtml\Information\Index as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Item;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\AuthorizationInterface;
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

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $item = $this->getMockBuilder(Item::class)->disableOriginalConstructor()->getMock();
        $item->method('getTitle')->willReturn('Dummy title');

        $parentItems = [$item];

        $menu = $this->getMockBuilder(Menu::class)->disableOriginalConstructor()->getMock();
        $menu->method('getParentItems')->willReturn($parentItems);

        $block = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMenuModel', 'setActive', 'toHtml'])
            ->getMock();
        $block->method('getMenuModel')->willReturn($menu);

        $layout = $this->getMockBuilder(LayoutInterface::class)->disableOriginalConstructor()->getMock();
        $layout->method('getBlock')->willReturn($block);
        $layout->method('createBlock')->willReturn($block);

        $title = $this->getMockBuilder(Title::class)->disableOriginalConstructor()->getMock();

        $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $config->method('getTitle')->willReturn($title);

        $page = $this->getMockBuilder(Page::class)->disableOriginalConstructor()->getMock();
        $page->method('getConfig')->willReturn($config);

        $view = $this->getMockBuilder(ViewInterface::class)->disableOriginalConstructor()->getMock();
        $view->method('getLayout')->willReturn($layout);
        $view->method('getPage')->willReturn($page);

        $authorization = $this->getMockBuilder(AuthorizationInterface::class)->disableOriginalConstructor()->getMock();
        $authorization->method('isAllowed')->willReturn(true);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getView')->willReturn($view);
        $context->method('getAuthorization')->willReturn($authorization);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, ['context' => $context]);
    }

    public function testExecute()
    {
        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }
}
