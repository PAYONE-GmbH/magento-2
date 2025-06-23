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
use Payone\Core\Model\Methods\Creditcard as MethodInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\ScopeInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Model\Risk\Addresscheck;

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

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->consumerscore = $this->getMockBuilder(Consumerscore::class)->disableOriginalConstructor()->getMock();
        $this->consumerscoreHelper = $this->getMockBuilder(ConsumerscoreHelper::class)->disableOriginalConstructor()->getMock();

        $addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $addresscheck->method('getPersonstatusMapping')->willReturn(['PPV' => 'R']);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'consumerscore' => $this->consumerscore,
            'consumerscoreHelper' => $this->consumerscoreHelper,
            'addresscheck' => $addresscheck
        ]);
    }

    public function testCreditratingNotNeeded()
    {
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(false);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getGrandTotal'])
            ->getMock();
        $quote->method('getGrandTotal')->willReturn(123.45);

        $result = $this->classToTest->isCreditratingNeeded($quote);
        $this->assertFalse($result);
    }

    public function testIsCreditratingNeededPaymentTypeMissing()
    {
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getConsumerscoreEnabledMethods')->willReturn([PayoneConfig::METHOD_DEBIT]);

        $infoInstance = $this->getMockBuilder(InfoInterface::class)->disableOriginalConstructor()->getMock();
        $infoInstance->method('getAdditionalInformation')->willReturn(false);

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_PAYPAL);
        $paymentMethod->method('getInfoInstance')->willReturn($infoInstance);

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
        $this->consumerscoreHelper->method('getConsumerscoreEnabledMethods')->willReturn(['payone_paypal', 'payone_creditcard']);

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
        $this->consumerscoreHelper->method('getConsumerscoreEnabledMethods')->willReturn(['payone_paypal', 'payone_creditcard']);

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
                ['Y', [PayoneConfig::METHOD_ADVANCE_PAYMENT, PayoneConfig::METHOD_CASH_ON_DELIVERY]],
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

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->addMethods(['getPayoneProtectScore'])->getMock();
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
            ->onlyMethods(['save'])
            ->addMethods(['getPayoneProtectScore', 'setPayoneProtectScore'])
            ->getMock();
        $address->method('getPayoneProtectScore')->willReturn($expected);
        $address->method('setPayoneProtectScore')->willReturn($address);

        $result = $this->classToTest->getScoreByCreditrating($address);
        $this->assertEquals($expected, $result);
    }

    public function testGetScoreByCreditratingScorePersonstatus()
    {
        $expected = 'R';

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID', 'score' => 'G', 'personstatus' => 'PPV']);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn($expected);

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->addMethods(['getPayoneProtectScore', 'setPayoneProtectScore'])
            ->getMock();
        $address->method('getPayoneProtectScore')->willReturn('G');
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
            ->onlyMethods(['save'])
            ->addMethods(['getPayoneAddresscheckScore', 'getPayoneProtectScore', 'setPayoneProtectScore'])
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

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->addMethods(['getQuote'])->getMock();
        $observer->method('getQuote')->willReturn($quote);

        return $observer;
    }

    public function testExecute()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID', 'score' => 'G']);
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('G');
        $this->consumerscoreHelper->method('getConsumerscoreEnabledMethods')->willReturn(['payone_paypal', 'payone_creditcard']);
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

    public function testExecuteNoAgreement()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID', 'score' => 'G']);
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('G');
        $this->consumerscoreHelper->method('getConsumerscoreEnabledMethods')->willReturn([PayoneConfig::METHOD_CREDITCARD]);
        $this->consumerscoreHelper->method('canShowAgreementMessage')->willReturn(true);
        $this->consumerscoreHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap(
                [
                    ['enabled', 'address_check', 'payone_protect', null, true]
                ]
            );

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->addMethods(['getPayoneAddresscheckScore', 'getPayoneProtectScore', 'setPayoneProtectScore'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn('G');
        $address->method('setPayoneProtectScore')->willReturn($address);

        $infoInstance = $this->getMockBuilder(InfoInterface::class)->disableOriginalConstructor()->getMock();
        $infoInstance->method('getAdditionalInformation')->willReturn(false);

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getCode')->willReturn(PayoneConfig::METHOD_CREDITCARD);
        $paymentMethod->method('getInfoInstance')->willReturn($infoInstance);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getPayment')->willReturn($payment);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->addMethods(['getQuote'])->getMock();
        $observer->method('getQuote')->willReturn($quote);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteNoQuote()
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->addMethods(['getQuote'])->getMock();
        $observer->method('getQuote')->willReturn(null);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID', 'score' => 'G']);
        $this->consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);
        $this->consumerscoreHelper->method('getWorstScore')->willReturn('Y');
        $this->consumerscoreHelper->method('getConsumerscoreEnabledMethods')->willReturn(['payone_paypal', 'payone_creditcard']);
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
                ['Y', [PayoneConfig::METHOD_ADVANCE_PAYMENT, PayoneConfig::METHOD_CASH_ON_DELIVERY]],
                ['R', [PayoneConfig::METHOD_DEBIT, PayoneConfig::METHOD_BNPL_DEBIT]]
            ]);

        $observer = $this->getExecuteObserver();

        $this->expectException(LocalizedException::class);
        $this->classToTest->execute($observer);
    }
}
