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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Plugins;

use Magento\Quote\Model\Quote;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Plugins\QuoteValidator as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Model\QuoteValidator as OrigQuoteValidator;
use Magento\Quote\Model\Quote\Payment;

class QuoteValidatorTest extends BaseTestCase
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);
    }

    public function testAroundValidateBeforeSubmit()
    {
        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethod')->willReturn(PayoneConfig::METHOD_INVOICE);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $subject = $this->getMockBuilder(OrigQuoteValidator::class)->disableOriginalConstructor()->getMock();
        $proceed = function ($quote) use ($subject) {
            return $subject;
        };

        $result = $this->classToTest->aroundValidateBeforeSubmit($subject, $proceed, $quote);
        $this->assertInstanceOf(OrigQuoteValidator::class, $result);
    }

    public function testAroundValidateBeforeSubmitAmazonPay()
    {
        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethod')->willReturn(PayoneConfig::METHOD_AMAZONPAY);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $subject = $this->getMockBuilder(OrigQuoteValidator::class)->disableOriginalConstructor()->getMock();
        $proceed = function ($quote) use ($subject) {
            return $subject;
        };

        $result = $this->classToTest->aroundValidateBeforeSubmit($subject, $proceed, $quote);
        $this->assertInstanceOf(OrigQuoteValidator::class, $result);
    }
}
