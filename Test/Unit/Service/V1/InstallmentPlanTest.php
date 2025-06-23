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

namespace Payone\Core\Test\Unit\Service\V1\Data;

use Magento\Quote\Model\Quote\Address;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Service\V1\InstallmentPlan as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Service\V1\Data\InstallmentPlanResponse;
use Payone\Core\Api\Data\InstallmentPlanResponseInterfaceFactory;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Payone\Core\Model\Api\Request\Genericpayment\PreCheck;
use Payone\Core\Model\Api\Request\Genericpayment\Calculation;
use Payone\Core\Model\Api\Request\Genericpayment\InstallmentOptions;
use Payone\Core\Block\Payolution\InstallmentPlan;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Ratepay;
use Payone\Core\Model\Methods\Ratepay\Installment;

class InstallmentPlanTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var InstallmentPlanResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * @var PreCheck|\PHPUnit_Framework_MockObject_MockObject
     */
    private $precheck;

    /**
     * @var Calculation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $calculation;

    /**
     * @var InstallmentOptions|\PHPUnit_Framework_MockObject_MockObject
     */
    private $installmentOptions;

    /**
     * @var Ratepay
     */
    private $ratepayHelper;

    /**
     * @var Installment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ratepayInstallment;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(InstallmentPlanResponse::class);
        $responseFactory = $this->getMockBuilder(InstallmentPlanResponseInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $address->method('getCountryId')->willReturn('DE');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress'])
            ->addMethods(['getBaseGrandTotal'])
            ->getMock();
        $quote->method('getBaseGrandTotal')->willReturn(100);
        $quote->method('getBillingAddress')->willReturn($address);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods(['setInstallmentDraftLinks', 'setInstallmentWorkorderId'])
            ->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->precheck = $this->getMockBuilder(PreCheck::class)->disableOriginalConstructor()->getMock();
        $this->calculation = $this->getMockBuilder(Calculation::class)->disableOriginalConstructor()->getMock();
        $this->installmentOptions = $this->getMockBuilder(InstallmentOptions::class)->disableOriginalConstructor()->getMock();
        $this->ratepayInstallment = $this->getMockBuilder(Installment::class)->disableOriginalConstructor()->getMock();

        $block = $this->getMockBuilder(InstallmentPlan::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml'])
            ->addMethods(['setInstallmentData', 'setCode'])
            ->getMock();
        $block->method('toHtml')->willReturn('InstallmentPlanHtml');

        $this->ratepayHelper = $this->getMockBuilder(Ratepay::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'checkoutSession' => $checkoutSession,
            'precheck' => $this->precheck,
            'calculation' => $this->calculation,
            'installmentOptions' => $this->installmentOptions,
            'block' => $block,
            'ratepayHelper' => $this->ratepayHelper,
            'ratepayInstallment' => $this->ratepayInstallment,
        ]);
    }

    public function testGetInstallmentPlan()
    {
        $precheck = ['status' => 'OK'];
        $this->precheck->method('sendRequest')->willReturn($precheck);

        $calculation = [
            'status' => 'OK',
            'workorderid' => 'WORKORDERID',
            'add_paydata[PaymentDetails_1_Duration]' => '3',
            'add_paydata[PaymentDetails_1_MinimumInstallmentFee]' => '0',
            'add_paydata[PaymentDetails_1_InterestRate]' => '14.95',
            'add_paydata[PaymentDetails_1_Usage]' => 'Calculated by calculation service',
            'add_paydata[PaymentDetails_1_EffectiveInterestRate]' => '16.03',
            'add_paydata[PaymentDetails_1_TotalAmount]' => '1278.09',
            'add_paydata[PaymentDetails_1_OriginalAmount]' => '1249.50',
            'add_paydata[PaymentDetails_1_Currency]' => 'EUR',
            'add_paydata[PaymentDetails_1_StandardCreditInformationUrl]' => 'http://installmentdraft.test',
            'add_paydata[PaymentDetails_1_Installment_1_Amount]' => '426.03',
            'add_paydata[PaymentDetails_1_Installment_1_Due]' => '2017-08-20',
            'add_paydata[PaymentDetails_1_Installment_2_Amount]' => '426.03',
            'add_paydata[PaymentDetails_1_Installment_2_Due]' => '2017-09-20',
            'add_paydata[PaymentDetails_1_Installment_3_Amount]' => '426.03',
            'add_paydata[PaymentDetails_1_Installment_3_Due]' => '2017-10-20',
        ];
        $this->calculation->method('sendRequest')->willReturn($calculation);

        $result = $this->classToTest->getInstallmentPlan(4711, '19900909');
        $this->assertTrue($result->getSuccess());
    }

    public function testGetInstallmentPlanErrorPre()
    {
        $precheck = ['status' => 'ERROR', 'errorcode' => '123', 'customermessage' => 'error'];
        $this->precheck->method('sendRequest')->willReturn($precheck);

        $result = $this->classToTest->getInstallmentPlan(4711, '19900909');
        $this->assertFalse($result->getSuccess());
    }

    public function testGetInstallmentPlanErrorCalc()
    {
        $precheck = ['status' => 'OK'];
        $this->precheck->method('sendRequest')->willReturn($precheck);

        $calculation = ['status' => 'ERROR', 'errorcode' => '123', 'customermessage' => 'error'];
        $this->calculation->method('sendRequest')->willReturn($calculation);

        $result = $this->classToTest->getInstallmentPlan(4711, '19900909');
        $this->assertFalse($result->getSuccess());
    }

    public function testGetInstallmentPlanErrorUnknown()
    {
        $this->precheck->method('sendRequest')->willReturn(false);

        $result = $this->classToTest->getInstallmentPlan(4711, '19900909');
        $this->assertFalse($result->getSuccess());
    }

    public function testGetInstallmentPlanRatepay()
    {
        $this->ratepayHelper->method('getRatepayShopId')->willReturn('test');

        $calculation = [
            'status' => 'OK',
            'workorderid' => 'WORKORDERID',
            'add_paydata[unit-test]' => '4711',
        ];
        $this->calculation->method('sendRequestRatepay')->willReturn($calculation);

        $result = $this->classToTest->getInstallmentPlanRatepay(4711, 'calculation-by-rate', 5);
        $this->assertTrue($result->getSuccess());
    }

    public function testGetInstallmentPlanBNPL()
    {
        $response = [
            'status' => 'OK',
            'workorderid' => 'WORKORDERID',
            'add_paydata[unit-test_1]' => '4711',
            'add_paydata[unit-test_2]' => '4711',
            'add_paydata[amount]' => '4711',
        ];
        $this->installmentOptions->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->getInstallmentPlanBNPL(4711, PayoneConfig::METHOD_BNPL_INSTALLMENT);
        $this->assertTrue($result->getSuccess());
    }

    public function testGetAllowedMonths()
    {
        $this->ratepayInstallment->method('getAllowedMonths')->willReturn([3, 6, 12]);

        $result = $this->classToTest->getAllowedMonths(123);
        $this->assertTrue($result->getSuccess());
    }
}
