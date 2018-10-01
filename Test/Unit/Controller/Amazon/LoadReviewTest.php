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

namespace Payone\Core\Test\Unit\Controller\Mandate;

use Magento\Quote\Model\Quote;
use Payone\Core\Controller\Amazon\LoadReview as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Exception\AuthorizationException;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Layout;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration;
use Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails;
use Payone\Core\Model\Api\Request\Genericpayment\SetOrderReferenceDetails;
use Magento\Quote\Model\Quote\Payment;
use Payone\Core\Helper\Order;
use Payone\Core\Helper\Checkout;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Response\RedirectInterface;

class LoadReviewTest extends BaseTestCase
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
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SetOrderReferenceDetails
     */
    private $setOrderReferenceDetails;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $result = $this->getMockBuilder(Json::class)->disableOriginalConstructor()->getMock();
        $result->method('setData')->willReturn($result);

        $resultJsonFactory = $this->getMockBuilder(JsonFactory::class)->disableOriginalConstructor()->getMock();
        $resultJsonFactory->method('create')->willReturn($result);

        $this->request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();

        $urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $urlBuilder->method('getUrl')->willReturn('http://www.test.com');

        $response = $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock();

        $messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();
        $messageManager->method('addErrorMessage')->willReturn($messageManager);

        $redirect = $this->getMockBuilder(RedirectInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getUrl')->willReturn($urlBuilder);
        $context->method('getResponse')->willReturn($response);
        $context->method('getMessageManager')->willReturn($messageManager);
        $context->method('getRedirect')->willReturn($redirect);

        $layout = $this->getMockBuilder(Layout::class)->disableOriginalConstructor()->getMock();
        $layout->method('getOutput')->willReturn('output');

        $page = $this->getMockBuilder(Page::class)->disableOriginalConstructor()->getMock();
        $page->method('getLayout')->willReturn($layout);

        $pageFactory = $this->getMockBuilder(PageFactory::class)->disableOriginalConstructor()->getMock();
        $pageFactory->method('create')->willReturn($page);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEmail', 'getShippingMethod', 'setShippingMethod'])
            ->getMock();
        $address->method('getEmail')->willReturn('test@test.de');
        $address->method('getShippingMethod')->willReturn('free');
        $address->method('setShippingMethod')->willReturn($address);

        $shipping = $this->getMockBuilder(ShippingInterface::class)->disableOriginalConstructor()->getMock();
        $shipping->method('setMethod')->willReturn($shipping);

        $assignment = $this->getMockBuilder(ShippingAssignmentInterface::class)->disableOriginalConstructor()->getMock();
        $assignment->method('getShipping')->willReturn($shipping);
        $aAssignments = [$assignment];

        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)->disableOriginalConstructor()->getMock();
        $extensionAttributes->method('getShippingAssignments')->willReturn($aAssignments);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPayment',
                'collectTotals',
                'setCustomerId',
                'setCustomerEmail',
                'setCustomerIsGuest',
                'setCustomerGroupId',
                'getBillingAddress',
                'getShippingAddress',
                'setIsActive',
                'save',
                'getIsVirtual',
                'getExtensionAttributes',
            ])
            ->getMock();
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('collectTotals')->willReturn($quote);
        $quote->method('setCustomerId')->willReturn($quote);
        $quote->method('setCustomerEmail')->willReturn($quote);
        $quote->method('setCustomerIsGuest')->willReturn($quote);
        $quote->method('setCustomerGroupId')->willReturn($quote);
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('setIsActive')->willReturn($quote);
        $quote->method('getIsVirtual')->willReturn(false);
        $quote->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getQuote',
                'getAmazonWorkorderId',
                'setAmazonWorkorderId',
                'unsAmazonWorkorderId',
                'getAmazonAddressToken',
                'setAmazonAddressToken',
                'unsAmazonAddressToken',
                'getAmazonReferenceId',
                'setAmazonReferenceId',
                'unsAmazonReferenceId',
                'getOrderReferenceDetailsExecuted',
                'setOrderReferenceDetailsExecuted',
                'unsOrderReferenceDetailsExecuted',
            ])
            ->getMock();
        $checkoutSession->method('getAmazonWorkorderId')->willReturn(null);
        $checkoutSession->method('getQuote')->willReturn($quote);
        $checkoutSession->method('getOrderReferenceDetailsExecuted')->willReturn(false);

        $responseGetConfiguration = [
            'status' => 'OK',
            'workorderid' => '12345',
        ];
        $getConfiguration = $this->getMockBuilder(GetConfiguration::class)->disableOriginalConstructor()->getMock();
        $getConfiguration->method('sendRequest')->willReturn($responseGetConfiguration);

        $responseGetOrderReferenceDetails = [
            'status' => 'OK',
        ];
        $getOrderReferenceDetails = $this->getMockBuilder(GetOrderReferenceDetails::class)->disableOriginalConstructor()->getMock();
        $getOrderReferenceDetails->method('sendRequest')->willReturn($responseGetOrderReferenceDetails);

        $this->setOrderReferenceDetails = $this->getMockBuilder(SetOrderReferenceDetails::class)->disableOriginalConstructor()->getMock();

        $orderHelper = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $orderHelper->method('updateAddresses')->willReturn($quote);

        $checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();
        $checkoutHelper->method('getCurrentCheckoutMethod')->willReturn(Onepage::METHOD_GUEST);

        $this->cartManagement = $this->getMockBuilder(CartManagementInterface::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'resultJsonFactory' => $resultJsonFactory,
            'pageFactory' => $pageFactory,
            'checkoutSession' => $checkoutSession,
            'getConfiguration' => $getConfiguration,
            'getOrderReferenceDetails' => $getOrderReferenceDetails,
            'setOrderReferenceDetails' => $this->setOrderReferenceDetails,
            'orderHelper' => $orderHelper,
            'checkoutHelper' => $checkoutHelper,
            'cartManagement' => $this->cartManagement
        ]);
    }

    public function testExecuteConfirmSelection()
    {
        $this->request->method('getParam')->willReturn('confirmSelection');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecutePlaceOrderNotOk()
    {
        $this->request->method('getParam')->willReturn('placeOrder');

        $this->setOrderReferenceDetails->method('sendRequest')->willReturn(['status' => 'ERROR']);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecutePlaceOrderOk()
    {
        $this->request->method('getParam')->willReturn('placeOrder');

        $this->setOrderReferenceDetails->method('sendRequest')->willReturn(['status' => 'OK']);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecutePlaceOrderAuthException()
    {
        $this->request->method('getParam')->willReturn('placeOrder');

        $this->setOrderReferenceDetails->method('sendRequest')->willReturn(['status' => 'OK']);

        $exception = $this->getMockBuilder(AuthorizationException::class)->disableOriginalConstructor()->getMock();
        $exception->method('getResponse')->willReturn(['status' => 'ERROR', 'errorcode' => 982]);
        $this->cartManagement->method('placeOrder')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecutePlaceOrderException()
    {
        $this->request->method('getParam')->willReturn('placeOrder');

        $this->setOrderReferenceDetails->method('sendRequest')->willReturn(['status' => 'OK']);

        $exception = new \Exception();
        $this->cartManagement->method('placeOrder')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateShipping()
    {
        $this->request->method('getParam')->willReturn('updateShipping');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteCancelToBasket()
    {
        $this->request->method('getParam')->willReturn('cancelToBasket');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
