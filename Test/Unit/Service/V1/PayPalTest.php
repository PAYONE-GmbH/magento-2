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
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Service\V1\Data\PayPalResponse;
use Payone\Core\Service\V1\PayPal as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Payone\Core\Test\Unit\BaseTestCase;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Payone\Core\Api\Data\PayPalResponseInterfaceFactory;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress;
use Magento\Quote\Model\Quote\Payment;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Payment\Helper\Data;

class PayPalTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var AmazonPayResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    private $paypalExpress;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(PayPalResponse::class);

        $responseFactory = $this->getMockBuilder(PayPalResponseInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'save',
                'getPayment',
                'setPayment',
            ])
            ->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods([
                'setIsPayonePayPalExpress',
                'setPayoneWorkorderId',
                'setPayoneQuoteComparisonString',
            ])
            ->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->paypalExpress = $this->getMockBuilder(PayPalExpress::class)->disableOriginalConstructor()->getMock();

        $checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();
        $checkoutHelper->method('getQuoteComparisonString')->willReturn('0815');

        $methodInstance = $this->getMockBuilder(PayoneMethod::class)->disableOriginalConstructor()->getMock();
        
        $dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataHelper->method('getMethodInstance')->willReturn($methodInstance);

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'checkoutSession' => $checkoutSession,
            'paypalRequest' => $this->paypalExpress,
            'checkoutHelper' => $checkoutHelper,
            'dataHelper' => $dataHelper,
        ]);
    }

    public function testStartPayPalExpress()
    {
        $this->paypalExpress->method('sendRequest')->willReturn([
            'status' => 'REDIRECT',
            'workorderid' => '4711',
            'add_paydata[orderId]' => '0815',
        ]);

        $result = $this->classToTest->startPayPalExpress(4711);
        $this->assertInstanceOf(PayPalResponse::class, $result);
        $this->assertTrue($result->getSuccess());
    }

    public function testStartPayPalExpressError()
    {
        $this->paypalExpress->method('sendRequest')->willReturn([
            'status' => 'ERROR',
            'customermessage' => 'error_message',
        ]);

        $result = $this->classToTest->startPayPalExpress(4711);
        $this->assertInstanceOf(PayPalResponse::class, $result);
        $this->assertFalse($result->getSuccess());
    }
}
