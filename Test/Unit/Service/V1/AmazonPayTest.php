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

namespace Payone\Core\Test\Unit\Service\V1\Data;

use Magento\Quote\Model\Quote;
use Payone\Core\Helper\Checkout;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Service\V1\AmazonPay as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Payone\Core\Service\V1\Data\EditAddressResponse;
use Payone\Core\Service\V1\Data\EditAddressResponseFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Payone\Core\Service\V1\Data\AmazonPayResponse;
use Payone\Core\Api\Data\AmazonPayResponseInterfaceFactory;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration;
use Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails;
use Payone\Core\Model\Api\Request\Genericpayment\SetOrderReferenceDetails;
use Payone\Core\Model\Api\Request\Genericpayment\CreateCheckoutSessionPayload;
use Payone\Core\Helper\Order;
use Magento\Quote\Model\Quote\Payment;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;

class AmazonPayTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var AmazonPayResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * @var CreateCheckoutSessionPayload|\PHPUnit\Framework\MockObject\MockObject
     */
    private $createCheckoutSessionPayload;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(AmazonPayResponse::class);

        $responseFactory = $this->getMockBuilder(AmazonPayResponseInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $address = $this->getMockBuilder(CustomerAddressInterface::class)->disableOriginalConstructor()->getMock();

        $addressRepository = $this->getMockBuilder(AddressRepository::class)->disableOriginalConstructor()->getMock();
        $addressRepository->method('getById')->willReturn($address);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getEmail')->willReturn('test@test.de');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'save',
                'getPayment',
                'getBillingAddress',
                'collectTotals',
                'setIsActive',
                'setCustomerIsGuest',
                'setPayment',
            ])
            ->addMethods([
                'setCustomerId',
                'setCustomerEmail',
                'setCustomerGroupId',
                'setInventoryProcessed',
            ])
            ->getMock();
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('collectTotals')->willReturn($quote);
        $quote->method('setIsActive')->willReturn($quote);
        $quote->method('setCustomerId')->willReturn($quote);
        $quote->method('setCustomerEmail')->willReturn($quote);
        $quote->method('setCustomerIsGuest')->willReturn($quote);
        $quote->method('setCustomerGroupId')->willReturn($quote);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods([
                'getAmazonWorkorderId',
                'setAmazonWorkorderId',
                'setAmazonAddressToken',
                'setAmazonReferenceId',
                'setPayoneIsAmazonPayExpressPayment',
                'setPayoneWorkorderId',
                'setPayoneQuoteComparisonString',
                'getPayoneAmazonPayPayload',
                'getPayoneAmazonPaySignature',
                'setPayoneCustomerIsRedirected',
            ])
            ->getMock();
        $checkoutSession->method('getAmazonWorkorderId')->willReturn(null);
        $checkoutSession->method('getQuote')->willReturn($quote);
        $checkoutSession->method('getPayoneAmazonPayPayload')->willReturn('test');
        $checkoutSession->method('getPayoneAmazonPaySignature')->willReturn('test');

        $aGetConfigurationResponse = [
            'status' => 'OK',
            'workorderid' => '12345',
        ];

        $getConfiguration = $this->getMockBuilder(GetConfiguration::class)->disableOriginalConstructor()->getMock();
        $getConfiguration->method('sendRequest')->willReturn($aGetConfigurationResponse);

        $aGetOrderReferenceDetailsResponse = ['status' => 'OK'];

        $getOrderReferenceDetails = $this->getMockBuilder(GetOrderReferenceDetails::class)->disableOriginalConstructor()->getMock();
        $getOrderReferenceDetails->method('sendRequest')->willReturn($aGetOrderReferenceDetailsResponse);

        $orderHelper = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $orderHelper->method('updateAddresses')->willReturn($quote);

        $aSetOrderReferenceDetailsResponse = ['status' => 'OK'];

        $setOrderReferenceDetails = $this->getMockBuilder(SetOrderReferenceDetails::class)->disableOriginalConstructor()->getMock();
        $setOrderReferenceDetails->method('sendRequest')->willReturn($aSetOrderReferenceDetailsResponse);

        $this->createCheckoutSessionPayload = $this->getMockBuilder(CreateCheckoutSessionPayload::class)->disableOriginalConstructor()->getMock();

        $checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();
        $checkoutHelper->method('getCurrentCheckoutMethod')->willReturn(Onepage::METHOD_GUEST);

        $block = $this->getMockBuilder(BlockInterface::class)->disableOriginalConstructor()->getMock();
        $block->method('toHtml')->willReturn('test');

        $layout = $this->getMockBuilder(LayoutInterface::class)->disableOriginalConstructor()->getMock();
        $layout->method('getBlock')->willReturn($block);

        $view = $this->getMockBuilder(ViewInterface::class)->disableOriginalConstructor()->getMock();
        $view->method('getLayout')->willReturn($layout);

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'addressRepository' => $addressRepository,
            'checkoutSession' => $checkoutSession,
            'getConfiguration' => $getConfiguration,
            'getOrderReferenceDetails' => $getOrderReferenceDetails,
            'setOrderReferenceDetails' => $setOrderReferenceDetails,
            'createCheckoutSessionPayload' => $this->createCheckoutSessionPayload,
            'orderHelper' => $orderHelper,
            'checkoutHelper' => $checkoutHelper,
            'view' => $view
        ]);
    }

    public function testGetWorkorderId()
    {
        $result = $this->classToTest->getWorkorderId('123', '123');
        $this->assertInstanceOf(AmazonPayResponse::class, $result);
    }

    public function testGetAmazonPayApbSession()
    {
        $result = $this->classToTest->getAmazonPayApbSession(4711);
        $this->assertInstanceOf(AmazonPayResponse::class, $result);
        $this->assertTrue($result->getSuccess());
    }

    public function testGetCheckoutSessionPayload()
    {
        $this->createCheckoutSessionPayload->method('sendRequest')->willReturn([
            'status' => 'OK',
            'add_paydata[signature]' => 'test',
            'add_paydata[payload]' => 'test',
            'workorderid' => 'test',
        ]);

        $result = $this->classToTest->getCheckoutSessionPayload(4711);
        $this->assertInstanceOf(AmazonPayResponse::class, $result);
        $this->assertTrue($result->getSuccess());
    }

    public function testGetCheckoutSessionPayloadError()
    {
        $this->createCheckoutSessionPayload->method('sendRequest')->willReturn([
            'status' => 'ERROR',
            'customermessage' => 'ERROR',
        ]);

        $result = $this->classToTest->getCheckoutSessionPayload(4711);
        $this->assertInstanceOf(AmazonPayResponse::class, $result);
        $this->assertFalse($result->getSuccess());
    }
}
