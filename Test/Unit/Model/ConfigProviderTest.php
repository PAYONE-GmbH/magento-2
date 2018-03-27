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

namespace Payone\Core\Test\Unit\Model;

use Payone\Core\Model\ConfigProvider as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Helper\Data;
use Payone\Core\Helper\Country;
use Payone\Core\Helper\Customer;
use Payone\Core\Helper\Payment;
use Payone\Core\Helper\HostedIframe;
use Payone\Core\Helper\Request;
use Magento\Framework\Escaper;
use Payone\Core\Helper\Consumerscore;
use Payone\Core\Model\PayoneConfig;
use Magento\Payment\Model\Method\AbstractMethod;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Checkout\Model\Session;

class ConfigProviderTest extends BaseTestCase
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
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataHelper;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $countryHelper = $this->getMockBuilder(Country::class)->disableOriginalConstructor()->getMock();
        $countryHelper->method('getDebitSepaCountries')->willReturn([['id' => 'DE', 'title' => 'Deutschland']]);
        $customerHelper = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();
        $customerHelper->method('customerHasGivenGender')->willReturn(true);
        $customerHelper->method('getCustomerBirthday')->willReturn(false);
        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('getAvailableCreditcardTypes')->willReturn(['V', 'M']);
        $paymentHelper->method('isMandateManagementActive')->willReturn(true);
        $paymentHelper->method('isCheckCvcActive')->willReturn(true);
        $paymentHelper->method('getBankaccountCheckBlockedMessage')->willReturn('Computer says no');
        $paymentHelper->method('getAvailablePaymentTypes')->willReturn([PayoneConfig::METHOD_CREDITCARD]);
        $hostedIframeHelper = $this->getMockBuilder(HostedIframe::class)->disableOriginalConstructor()->getMock();
        $hostedIframeHelper->method('getHostedFieldConfig')->willReturn(['fields' => ['cvc' => ['width' => '20px']]]);
        $requestHelper = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $requestHelper->method('getHostedIframeRequest')->willReturn(['request' => 'creditcardcheck']);
        $requestHelper->method('getConfigParam')->willReturn('value');
        $requestHelper->method('getBankaccountCheckRequest')->willReturn(['request' => 'bankaccountcheck']);
        $escaper = $this->getMockBuilder(Escaper::class)->disableOriginalConstructor()->getMock();
        $escaper->method('escapeHtml')->willReturn('html');
        $consumerscoreHelper = $this->getMockBuilder(Consumerscore::class)->disableOriginalConstructor()->getMock();
        $consumerscoreHelper->method('canShowPaymentHintText')->willReturn(true);
        $consumerscoreHelper->method('canShowAgreementMessage')->willReturn(true);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneCanceledPaymentMethod', 'unsPayoneCanceledPaymentMethod', 'getPayoneIsError'])
            ->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'dataHelper' => $this->dataHelper,
            'countryHelper' => $countryHelper,
            'customerHelper' => $customerHelper,
            'paymentHelper' => $paymentHelper,
            'hostedIframeHelper' => $hostedIframeHelper,
            'requestHelper' => $requestHelper,
            'escaper' => $escaper,
            'consumerscoreHelper' => $consumerscoreHelper,
            'checkoutSession' => $this->checkoutSession
        ]);
    }

    public function testGetConfig()
    {
        $method = $this->getMockBuilder(AbstractMethod::class)
            ->disableOriginalConstructor()
            ->setMethods(['getInstructions'])
            ->getMock();
        $method->method('getInstructions')->willReturn('Instruction');
        $this->dataHelper->method('getMethodInstance')->willReturn($method);

        $this->checkoutSession->method('getPayoneCanceledPaymentMethod')->willReturn(null);

        $result = $this->classToTest->getConfig();
        $this->assertNotEmpty($result);
    }

    public function testGetConfigNoInstance()
    {
        $this->dataHelper->method('getMethodInstance')->willReturn(null);

        $this->checkoutSession->method('getPayoneCanceledPaymentMethod')->willReturn('payone_creditcard');

        $result = $this->classToTest->getConfig();
        $this->assertNotEmpty($result);
    }
}
