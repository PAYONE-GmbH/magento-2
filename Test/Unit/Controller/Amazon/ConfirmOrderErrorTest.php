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

namespace Payone\Core\Test\Unit\Controller\Amazon;

use Magento\Quote\Model\Quote;
use Payone\Core\Controller\Amazon\ConfirmOrderError as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Url;
use Magento\Framework\App\RequestInterface;

class ConfirmOrderErrorTest extends BaseTestCase
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
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $resultRedirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $resultRedirect->method('setUrl')->willReturn($resultRedirect);

        $resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $resultFactory->method('create')->willReturn($resultRedirect);

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getMessageManager')->willReturn($messageManager);
        $context->method('getRequest')->willReturn($this->request);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'setIsPayoneRedirectCancellation',
                'unsAmazonWorkorderId',
                'unsAmazonAddressToken',
                'unsAmazonReferenceId',
                'unsOrderReferenceDetailsExecuted',
                'setTriggerInvalidPayment',
            ])
            ->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession
        ]);
    }

    public function testExecuteAbandoned()
    {
        $this->request->method('getParam')->willReturn('Abandoned');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecute()
    {
        $this->request->method('getParam')->willReturn('Not Abandoned');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
