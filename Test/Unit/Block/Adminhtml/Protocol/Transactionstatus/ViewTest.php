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

namespace Payone\Core\Test\Unit\Block\Adminhtml\Protocol\Transactionstatus;

use Payone\Core\Block\Adminhtml\Protocol\Transactionstatus\View as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Entities\TransactionStatusFactory;
use Payone\Core\Model\Entities\TransactionStatus;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Framework\UrlInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ViewTest extends BaseTestCase
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

        $status = $this->getMockBuilder(TransactionStatus::class)->disableOriginalConstructor()->getMock();
        $status->method('load')->willReturn($status);

        $statusFactory = $this->getMockBuilder(TransactionStatusFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $statusFactory->method('create')->willReturn($status);

        $request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $request->method('getParam')->willReturn('5');

        $buttonList = $this->getMockBuilder(ButtonList::class)->disableOriginalConstructor()->getMock();

        $urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $urlBuilder->method('getUrl')->willReturn('http://testdomain.com');

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($request);
        $context->method('getButtonList')->willReturn($buttonList);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'statusFactory' => $statusFactory
        ]);
    }

    public function testGetApiLogEntry()
    {
        $result = $this->classToTest->getTransactionStatusEntry();
        $this->assertInstanceOf(TransactionStatus::class, $result);
    }
}
