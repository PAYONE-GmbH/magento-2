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

namespace Payone\Core\Test\Unit\Model\Plugins;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Plugins\MethodList as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\MethodList;
use Payone\Core\Model\Api\Request\Consumerscore;
use Payone\Core\Helper\Consumerscore as ConsumerscoreHelper;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Payment\Model\MethodInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Model\ResourceModel\PaymentBan;
use Payone\Core\Model\Risk\Addresscheck;

class MethodListTest extends BaseTestCase
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
     * @var Consumerscore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consumerscore;

    /**
     * @var ConsumerscoreHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consumerscoreHelper;

    /**
     * @var PaymentBan|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentBan;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->consumerscore = $this->getMockBuilder(Consumerscore::class)->disableOriginalConstructor()->getMock();
        $this->consumerscoreHelper = $this->getMockBuilder(ConsumerscoreHelper::class)->disableOriginalConstructor()->getMock();
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getConfigParam')->willReturn(true);
        $this->consumerscoreHelper->expects($this->any())
            ->method('getAllowedMethodsForScore')
            ->willReturnMap([
                    ['Y', [PayoneConfig::METHOD_CREDITCARD, PayoneConfig::METHOD_ADVANCE_PAYMENT, PayoneConfig::METHOD_OBT_IDEAL]],
                    ['R', [PayoneConfig::METHOD_DEBIT, PayoneConfig::METHOD_CASH_ON_DELIVERY, PayoneConfig::METHOD_AMAZONPAY]],
                ]);


        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->addMethods(['getPayoneAddresscheckScore', 'setPayoneProtectScore', 'getPayoneProtectScore'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn('Y');
        $address->method('getPayoneProtectScore')->willReturn('Y');
        $address->method('setPayoneProtectScore')->willReturn($address);
        $address->method('save')->willReturn($address);

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomerId'])
            ->getMock();
        $this->quote->method('getShippingAddress')->willReturn($address);
        $this->quote->method('getGrandTotal')->willReturn(100.00);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods(['getPayonePaymentBans', 'getPayonePaymentWhitelist'])
            ->getMock();
        $checkoutSession->method('getQuote')->willReturn($this->quote);
        $checkoutSession->method('getPayonePaymentBans')->willReturn([PayoneConfig::METHOD_DEBIT => '2100-01-01 12:00:00']);
        $checkoutSession->method('getPayonePaymentWhitelist')->willReturn(['payone_creditcard', 'payone_paypal', 'payone_debit', 'payone_cash_on_delivery', 'payone_amazonpay', 'payone_klarna_base', 'payone_klarna_invoice']);

        $addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $addresscheck->method('getPersonstatusMapping')->willReturn(['PPV' => 'R']);

        $this->paymentBan = $this->getMockBuilder(PaymentBan::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'consumerscore' => $this->consumerscore,
            'consumerscoreHelper' => $this->consumerscoreHelper,
            'checkoutSession' => $checkoutSession,
            'paymentBan' => $this->paymentBan,
            'addresscheck' => $addresscheck,
        ]);
    }

    public function testAfterGetAvailableMethodsPreviousCheck()
    {
        $this->consumerscore->method('sendRequest')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('Y');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $paymentBanned = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentBanned->method('getCode')->willReturn(PayoneConfig::METHOD_DEBIT);

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_CASH_ON_DELIVERY);
        $paymentMethods = [$paymentBanned, $payment];

        $this->quote->method('getCustomerId')->willReturn('5');
        $this->paymentBan->method('getPaymentBans')->willReturn([]);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertInstanceOf(MethodInterface::class, array_shift($result));
    }

    public function testAfterGetAvailableMethods()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['score' => 'Y']);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('R');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_CASH_ON_DELIVERY);
        $paymentMethods = [$payment];

        $this->quote->method('getCustomerId')->willReturn('5');
        $this->paymentBan->method('getPaymentBans')->willReturn([]);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertInstanceOf(MethodInterface::class, $result[0]);
    }

    public function testAfterGetAvailableMethodsEmpty()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['score' => 'Y', 'personstatus' => 'PPV']);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('R');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_ADVANCE_PAYMENT);
        $paymentMethods = [$payment];

        $this->quote->method('getCustomerId')->willReturn('5');
        $this->paymentBan->method('getPaymentBans')->willReturn([]);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertEmpty($result);
    }

    public function testAfterGetAvailableMethodsBanRegistered()
    {
        $this->consumerscore->method('sendRequest')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('Y');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_DEBIT);
        $paymentMethods = [$payment];

        $this->quote->method('getCustomerId')->willReturn('5');
        $ban = [PayoneConfig::METHOD_DEBIT => '2100-01-01 12:00:00'];
        $this->paymentBan->method('getPaymentBans')->willReturn($ban);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertEmpty($result);
    }

    public function testAfterGetAvailableMethodsBanGuest()
    {
        $this->consumerscore->method('sendRequest')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('Y');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_DEBIT);
        $paymentMethods = [$payment];

        $this->quote->method('getCustomerId')->willReturn(null);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertEmpty($result);
    }

    public function testAfterGetAvailableMethodsRemoveAmazonPay()
    {
        $this->consumerscore->method('sendRequest')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('Y');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_AMAZONPAY);
        $paymentMethods = [$payment];

        $this->quote->method('getCustomerId')->willReturn('5');
        $this->paymentBan->method('getPaymentBans')->willReturn([]);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertEmpty($result);
    }

    public function testAfterGetAvailableMethodsRemoveNotWhitelisted()
    {
        $this->consumerscore->method('sendRequest')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('Y');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $payment = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment->method('getCode')->willReturn(PayoneConfig::METHOD_OBT_IDEAL);
        $paymentMethods = [$payment];

        $this->quote->method('getCustomerId')->willReturn('5');
        $this->paymentBan->method('getPaymentBans')->willReturn([]);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertEmpty($result);
    }

    public function testAfterGetAvailableMethodsKlarna()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['score' => 'Y']);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('G');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $paymentBase = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentBase->method('getCode')->willReturn(PayoneConfig::METHOD_KLARNA_BASE);
        $paymentInvoice = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentInvoice->method('getCode')->willReturn(PayoneConfig::METHOD_KLARNA_INVOICE);

        $paymentMethods = [$paymentBase, $paymentInvoice];

        $this->quote->method('getCustomerId')->willReturn('5');
        $this->paymentBan->method('getPaymentBans')->willReturn([]);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertCount(2, $result);
    }

    public function testAfterGetAvailableMethodsRemoveKlarna()
    {
        $this->consumerscore->method('sendRequest')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('G');

        $subject = $this->getMockBuilder(MethodList::class)->disableOriginalConstructor()->getMock();

        $paymentBase = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentBase->method('getCode')->willReturn(PayoneConfig::METHOD_KLARNA_BASE);

        $paymentMethods = [$paymentBase];

        $this->quote->method('getCustomerId')->willReturn('5');
        $this->paymentBan->method('getPaymentBans')->willReturn([]);

        $result = $this->classToTest->afterGetAvailableMethods($subject, $paymentMethods, $this->quote);
        $this->assertEmpty($result);
    }
}
