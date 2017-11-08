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

namespace Payone\Core\Test\Unit\Observer;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Observer\CheckoutSubmitBefore as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Api\Request\Consumerscore;
use Payone\Core\Helper\Consumerscore as ConsumerscoreHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\ScopeInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class CheckoutSubmitBeforeTest extends BaseTestCase
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

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->consumerscore = $this->getMockBuilder(Consumerscore::class)->disableOriginalConstructor()->getMock();
        $this->consumerscoreHelper = $this->getMockBuilder(ConsumerscoreHelper::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'consumerscore' => $this->consumerscore,
            'consumerscoreHelper' => $this->consumerscoreHelper
        ]);
    }

    public function testCreditratingNotNeeded()
    {
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(false);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGrandTotal'])
            ->getMock();
        $quote->method('getGrandTotal')->willReturn(123.45);

        $result = $this->classToTest->isCreditratingNeeded($quote);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeededPaymentTypeMissing()
    {
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getConfigParam')->willReturn(PayoneConfig::METHOD_CREDITCARD.','.PayoneConfig::METHOD_DEBIT);

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_PAYPAL);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $result = $this->classToTest->isCreditratingNeeded($quote);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeededNotAgreedByCustomer()
    {
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getConfigParam')->willReturn(PayoneConfig::METHOD_CREDITCARD.','.PayoneConfig::METHOD_DEBIT);

        $infoInstance = $this->getMockBuilder(InfoInterface::class)->disableOriginalConstructor()->getMock();
        $infoInstance->method('getAdditionalInformation')->willReturn(false);

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);
        $paymentMethod->method('getInfoInstance')->willReturn($infoInstance);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $result = $this->classToTest->isCreditratingNeeded($quote);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeeded()
    {
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getConfigParam')->willReturn(PayoneConfig::METHOD_CREDITCARD.','.PayoneConfig::METHOD_DEBIT);

        $infoInstance = $this->getMockBuilder(InfoInterface::class)->disableOriginalConstructor()->getMock();
        $infoInstance->method('getAdditionalInformation')->willReturn(true);

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);
        $paymentMethod->method('getInfoInstance')->willReturn($infoInstance);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $result = $this->classToTest->isCreditratingNeeded($quote);
        $this->assertTrue($result);
    }

    public function testIsPaymentApplicableForScoreGreen()
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->isPaymentApplicableForScore($quote, 'G');
        $this->assertTrue($result);
    }

    public function testIsPaymentApplicableForScoreYellow()
    {
        $this->consumerscoreHelper->expects($this->any())
            ->method('getAllowedMethodsForScore')
            ->willReturnMap([
                ['Y', [PayoneConfig::METHOD_ADVANCE_PAYMENT, PayoneConfig::METHOD_BILLSAFE]],
                ['R', [PayoneConfig::METHOD_DEBIT, PayoneConfig::METHOD_CREDITCARD]]
            ]);

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $result = $this->classToTest->isPaymentApplicableForScore($quote, 'Y');
        $this->assertTrue($result);

        $result = $this->classToTest->isPaymentApplicableForScore($quote, 'R');
        $this->assertTrue($result);

        $result = $this->classToTest->isPaymentApplicableForScore($quote, 'X');
        $this->assertFalse($result);
    }

    public function testCheckoutNeedsToBeStopped()
    {
        $this->consumerscoreHelper->method('getConfigParam')->willReturn('stop_checkout');

        $result = $this->classToTest->checkoutNeedsToBeStopped(['status' => 'VALID']);
        $this->assertFalse($result);

        $result = $this->classToTest->checkoutNeedsToBeStopped(['status' => 'ERROR']);
        $this->assertTrue($result);
    }

    public function testGetScoreByCreditrating()
    {
        $expected = 'G';

        $this->consumerscore->method('sendRequest')->willReturn(true);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->setMethods(['getPayoneProtectScore'])->getMock();
        $address->method('getPayoneProtectScore')->willReturn($expected);

        $result = $this->classToTest->getScoreByCreditrating($address);
        $this->assertEquals($expected, $result);
    }

    public function testGetScoreByCreditratingScoreIsset()
    {
        $expected = 'G';

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID', 'score' => $expected]);

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneProtectScore', 'setPayoneProtectScore', 'save'])
            ->getMock();
        $address->method('getPayoneProtectScore')->willReturn($expected);
        $address->method('setPayoneProtectScore')->willReturn($address);

        $result = $this->classToTest->getScoreByCreditrating($address);
        $this->assertEquals($expected, $result);
    }

    public function testGetScoreByCreditratingException()
    {
        $this->consumerscoreHelper->method('getConfigParam')->willReturn(null);
        $this->consumerscore->method('sendRequest')->willReturn(false);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $this->expectException(LocalizedException::class);
        $this->classToTest->getScoreByCreditrating($address);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getExecuteObserver()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneAddresscheckScore', 'getPayoneProtectScore', 'setPayoneProtectScore', 'save'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn('G');
        $address->method('setPayoneProtectScore')->willReturn($address);

        $infoInstance = $this->getMockBuilder(InfoInterface::class)->disableOriginalConstructor()->getMock();
        $infoInstance->method('getAdditionalInformation')->willReturn(true);

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);
        $paymentMethod->method('getInfoInstance')->willReturn($infoInstance);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getPayment')->willReturn($payment);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getQuote'])->getMock();
        $observer->method('getQuote')->willReturn($quote);

        return $observer;
    }

    public function testExecute()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID', 'score' => 'G']);
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('G');
        $this->consumerscoreHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap(
                [
                    ['enabled', 'address_check', 'payone_protect', null, true],
                    ['enabled_for_payment_methods', 'creditrating', 'payone_protect', null, PayoneConfig::METHOD_CREDITCARD.','.PayoneConfig::METHOD_DEBIT]
                ]
            );

        $observer = $this->getExecuteObserver();

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteNoQuote()
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->setMethods(['getQuote'])->getMock();
        $observer->method('getQuote')->willReturn(null);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID', 'score' => 'G']);
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('X');
        $this->consumerscoreHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap(
                [
                    ['enabled', 'address_check', 'payone_protect', null, true],
                    ['enabled_for_payment_methods', 'creditrating', 'payone_protect', null, PayoneConfig::METHOD_CREDITCARD.','.PayoneConfig::METHOD_DEBIT]
                ]
            );
        $this->consumerscoreHelper->expects($this->any())
            ->method('getAllowedMethodsForScore')
            ->willReturnMap([
                ['Y', [PayoneConfig::METHOD_ADVANCE_PAYMENT, PayoneConfig::METHOD_BILLSAFE]],
                ['R', [PayoneConfig::METHOD_DEBIT, PayoneConfig::METHOD_CREDITCARD]]
            ]);

        $observer = $this->getExecuteObserver();

        $this->expectException(LocalizedException::class);
        $this->classToTest->execute($observer);
    }
}
