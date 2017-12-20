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

namespace Payone\Core\Test\Unit\Block\Onepage;

use Magento\Sales\Model\Order;
use Payone\Core\Block\Payolution\InstallmentPlan as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Payone\Core\Helper\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class InstallmentPlanTest extends BaseTestCase
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
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, ['context' => $context]);
    }

    public function testFormatPrice()
    {
        $result = $this->classToTest->formatPrice(50000.95);
        $expected = '50000,95';
        $this->assertEquals($expected, $result);
    }

    public function testGetSelectLinkText()
    {
        $aInstallment = [
            'currency' => 'EUR',
            'duration' => '12',
            'installment' => [
                '1' => ['amount' => 5]
            ]
        ];
        $expected = '5,00 EUR per month - 12 installments';
        $result = $this->classToTest->getSelectLinkText($aInstallment);
        $this->assertEquals($expected, $result);
    }

    public function testGetPaymentInfoText()
    {
        $aInstallment = ['currency' => 'EUR'];
        $aPayment = ['amount' => 10, 'due' => '01.01.2018'];
        $expected = '5. Installment: 10,00 EUR (due 01.01.2018)';
        $result = $this->classToTest->getPaymentInfoText(5, $aInstallment, $aPayment);
        $this->assertEquals($expected, $result);
    }

    public function testGetDraftDownloadLink()
    {
        $expected = 'http://testdomain.com';
        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getDraftDownloadLink(5);
        $this->assertEquals($expected, $result);
    }
}
