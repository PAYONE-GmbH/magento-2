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

use Payone\Core\Controller\Payments\SetDefault as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey\Validator;
use Payone\Core\Model\ResourceModel\SavedPaymentData;
use Magento\Framework\Message\ManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class SetDefaultTest extends BaseTestCase
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
     * @var SavedPaymentData|\PHPUnit_Framework_MockObject_MockObject
     */
    private $savedPaymentData;

    /**
     * @var Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formKeyValidator;

    /**
     * @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $resultRedirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $resultRedirect->method('setPath')->willReturn($resultRedirect);
        $resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $resultRedirectFactory->method('create')->willReturn($resultRedirect);

        $request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $request->method('getParam')->willReturn(123);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultRedirectFactory')->willReturn($resultRedirectFactory);

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();
        $context->method('getMessageManager')->willReturn($this->messageManager);

        $customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $customerSession->method('getCustomerId')->willReturn(123);

        $this->savedPaymentData = $this->getMockBuilder(SavedPaymentData::class)->disableOriginalConstructor()->getMock();
        $this->formKeyValidator = $this->getMockBuilder(Validator::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'customerSession' => $customerSession,
            'savedPaymentData' => $this->savedPaymentData,
            'formKeyValidator' => $this->formKeyValidator
        ]);
    }

    public function testExecute()
    {
        $this->formKeyValidator->method('validate')->willReturn(true);
        $this->savedPaymentData->expects($this->once())->method('setDefault');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteInvalidFormKey()
    {
        $this->formKeyValidator->method('validate')->willReturn(false);
        $this->savedPaymentData->expects($this->never())->method('setDefault');
        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
