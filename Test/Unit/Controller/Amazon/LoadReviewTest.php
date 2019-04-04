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

use Magento\Framework\Exception\LocalizedException;
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
use Magento\Quote\Api\Data\CartExtension;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\Coupon;

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

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Coupon
     */
    private $coupon;

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
            ->setMethods(['getEmail', 'getShippingMethod', 'setShippingMethod', 'setCollectShippingRates'])
            ->getMock();
        $address->method('getEmail')->willReturn('test@test.de');
        $address->method('getShippingMethod')->willReturn('free');
        $address->method('setShippingMethod')->willReturn($address);

        $shipping = $this->getMockBuilder(ShippingInterface::class)->disableOriginalConstructor()->getMock();
        $shipping->method('setMethod')->willReturn($shipping);

        $assignment = $this->getMockBuilder(ShippingAssignmentInterface::class)->disableOriginalConstructor()->getMock();
        $assignment->method('getShipping')->willReturn($shipping);
        $aAssignments = [$assignment];

        $extensionAttributes = $this->getMockBuilder(CartExtension::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAssignments'])
            ->getMock();
        $extensionAttributes->method('getShippingAssignments')->willReturn($aAssignments);

        $this->quote = $this->getMockBuilder(Quote::class)
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
                'getCouponCode',
                'getItemsCount',
                'setCouponCode',
            ])
            ->getMock();
        $this->quote->method('getPayment')->willReturn($payment);
        $this->quote->method('collectTotals')->willReturn($this->quote);
        $this->quote->method('setCustomerId')->willReturn($this->quote);
        $this->quote->method('setCustomerEmail')->willReturn($this->quote);
        $this->quote->method('setCustomerIsGuest')->willReturn($this->quote);
        $this->quote->method('setCustomerGroupId')->willReturn($this->quote);
        $this->quote->method('getBillingAddress')->willReturn($address);
        $this->quote->method('getShippingAddress')->willReturn($address);
        $this->quote->method('setIsActive')->willReturn($this->quote);
        $this->quote->method('getIsVirtual')->willReturn(false);
        $this->quote->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->quote->method('setCouponCode')->willReturn($this->quote);

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
        $checkoutSession->method('getQuote')->willReturn($this->quote);
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
        $orderHelper->method('updateAddresses')->willReturn($this->quote);

        $checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();
        $checkoutHelper->method('getCurrentCheckoutMethod')->willReturn(Onepage::METHOD_GUEST);

        $this->cartManagement = $this->getMockBuilder(CartManagementInterface::class)->disableOriginalConstructor()->getMock();

        $this->coupon = $this->getMockBuilder(Coupon::class)->disableOriginalConstructor()->getMock();

        $couponFactory = $this->getMockBuilder(CouponFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $couponFactory->method('create')->willReturn($this->coupon);

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
            'cartManagement' => $this->cartManagement,
            'couponFactory' => $couponFactory,
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

    public function testExecuteUpdateCouponUsed()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, '12345'],
        ]);
        $this->quote->method('getCouponCode')->willReturn('12345');
        $this->quote->method('getItemsCount')->willReturn(5);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateCouponNoItemCountLengthInvalid()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, '123456789012345678911234567892123456789312345678941234567895123456789612345678971234567898123456789912345678901234567891123456789212345678931234567894123456789512345678961234567897123456789812345678991234567890123456789112345678921234567893123456789412345678951234567896123456789712345678981234567899'],
        ]);
        $this->quote->method('getCouponCode')->willReturn('12345');
        $this->quote->method('getItemsCount')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateCouponValid()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, '12345'],
        ]);
        $this->quote->method('getCouponCode')->willReturn('12345');
        $this->quote->method('getItemsCount')->willReturn(false);

        $this->coupon->method('getId')->willReturn('123');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateCouponNotLoaded()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, '12345'],
        ]);
        $this->quote->method('getCouponCode')->willReturn('12345');
        $this->quote->method('getItemsCount')->willReturn(false);

        $this->coupon->method('getId')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateCouponLocalizedException()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, '12345'],
        ]);
        $this->quote->method('getCouponCode')->willReturn('12345');
        $this->quote->method('getItemsCount')->willReturn(false);

        $exception = $this->getMockBuilder(LocalizedException::class)->disableOriginalConstructor()->getMock();
        $exception->method('getMessage')->willReturn('Error');

        $this->coupon->method('load')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateCouponException()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, '12345'],
        ]);
        $this->quote->method('getCouponCode')->willReturn('12345');
        $this->quote->method('getItemsCount')->willReturn(false);

        $exception = new \Exception('Error');
        $this->coupon->method('load')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateCouponNotValid()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, '12345'],
        ]);
        $this->quote->method('getCouponCode')->willReturn('123');
        $this->quote->method('getItemsCount')->willReturn(5);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteUpdateCouponOnlyOldCoupon()
    {
        $this->request->method('getParam')->willReturnMap([
            ['action', null, 'updateCoupon'],
            ['remove', null, null],
            ['couponCode', null, null],
        ]);
        $this->quote->method('getCouponCode')->willReturn('123');
        $this->quote->method('getItemsCount')->willReturn(5);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
}
