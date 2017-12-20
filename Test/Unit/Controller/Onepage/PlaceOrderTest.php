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

namespace Payone\Core\Test\Unit\Controller\Onepage;

use Magento\Quote\Model\Quote;
use Payone\Core\Controller\Onepage\PlaceOrder as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\App\Console\Response;
use Magento\Store\App\Response\Redirect as RedirectResponse;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class PlaceOrderTest extends BaseTestCase
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
     * @var AgreementsValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $agreementValidator;

    /**
     * @var CartManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cartManagement;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $redirectResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $redirect = $this->getMockBuilder(RedirectResponse::class)->disableOriginalConstructor()->getMock();
        $redirect->method('redirect')->willReturn($redirectResponse);

        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect'])
            ->getMock();

        $url = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(Http::class)->disableOriginalConstructor()->setMethods(['getBeforeForwardInfo'])->getMock();

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRedirect')->willReturn($redirect);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getResponse')->willReturn($response);
        $context->method('getUrl')->willReturn($url);
        $context->method('getMessageManager')->willReturn($messageManager);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getIsVirtual')->willReturn(false);
        $quote->method('getId')->willReturn('12345');
        $quote->method('setIsActive')->willReturn($quote);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'setLastQuoteId', 'setLastSuccessQuoteId', 'unsPayoneWorkorderId'])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->checkoutSession->method('setLastQuoteId')->willReturn($this->checkoutSession);
        $this->checkoutSession->method('setLastSuccessQuoteId')->willReturn($this->checkoutSession);

        $this->agreementValidator = $this->getMockBuilder(AgreementsValidatorInterface::class)->disableOriginalConstructor()->getMock();
        $this->agreementValidator->method('isValid')->willReturn(false);

        $this->cartManagement = $this->getMockBuilder(CartManagementInterface::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'agreementValidator' => $this->agreementValidator,
            'cartManagement' => $this->cartManagement
        ]);
    }

    public function testExecute()
    {
        $this->request->method('getBeforeForwardInfo')->willReturn(false);
        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteValidation()
    {
        $this->request->method('getBeforeForwardInfo')->willReturn([]);
        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $exception = new \Exception();
        $this->cartManagement->method('placeOrder')->willThrowException($exception);

        $this->request->method('getBeforeForwardInfo')->willReturn(false);
        $result = $this->classToTest->execute();
        $this->assertNull($result);
    }
}
