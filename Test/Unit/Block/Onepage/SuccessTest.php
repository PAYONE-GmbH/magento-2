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

namespace Payone\Core\Test\Unit\Block\Onepage;

use Magento\Sales\Model\Order;
use Payone\Core\Block\Onepage\Success as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Payone\Core\Helper\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class SuccessTest extends BaseTestCase
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
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $eventManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $scopeConfig->method('getValue')->willReturn(true);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $context->method('getEventManager')->willReturn($eventManager);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLastRealOrder', 'getPayoneInstructionNotes', 'unsPayoneInstructionNotes'])
            ->getMock();

        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'paymentHelper' => $this->paymentHelper
        ]);
    }

    public function testShowMandateLink()
    {
        $this->paymentHelper->method('isMandateManagementDownloadActive')->willReturn(true);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneMandateId'])
            ->getMock();
        $order->method('getPayoneMandateId')->willReturn('15');

        $this->checkoutSession->method('getLastRealOrder')->willReturn($order);

        $result = $this->classToTest->showMandateLink();
        $this->assertTrue($result);
    }

    public function testShowMandateLinkFalse()
    {
        $this->paymentHelper->method('isMandateManagementDownloadActive')->willReturn(false);

        $result = $this->classToTest->showMandateLink();
        $this->assertFalse($result);
    }

    public function testGetMandateDownloadUrl()
    {
        $expected = 'http://testdomain.com';
        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getMandateDownloadUrl();
        $this->assertEquals($expected, $result);
    }

    public function testGetInstructionNotes()
    {
        $expected = 'Instruction text';
        $this->checkoutSession->method('getPayoneInstructionNotes')->willReturn($expected);

        $result = $this->classToTest->getInstructionNotes();
        $this->assertEquals($expected, $result);
    }

    public function testToHtml()
    {
        $this->checkoutSession->method('getPayoneInstructionNotes')->willReturn('Dummy text');

        $result = $this->classToTest->toHtml();
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    public function testToHtmlEmpty()
    {
        $this->paymentHelper->method('isMandateManagementDownloadActive')->willReturn(false);
        $this->checkoutSession->method('getPayoneInstructionNotes')->willReturn(null);

        $result = $this->classToTest->toHtml();
        $expected = '';
        $this->assertEquals($expected, $result);
    }
}
