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

use Payone\Core\Block\Onepage\Debit as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class DebitTest extends BaseTestCase
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

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPayoneMandate', 'getPayoneDebitError', 'unsPayoneDebitError'])
            ->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession
        ]);
    }

    public function testGetMandateText()
    {
        $expected = 'test text';

        $this->checkoutSession->method('getPayoneMandate')->willReturn([
            'mandate_status' => 'pending',
            'mandate_text' => urlencode($expected)
        ]);

        $result = $this->classToTest->getMandateText();
        $this->assertEquals($expected, $result);
    }

    public function testGetMandateTextFalse()
    {
        $this->checkoutSession->method('getPayoneMandate')->willReturn(false);

        $result = $this->classToTest->getMandateText();
        $this->assertFalse($result);
    }

    public function testGetMandateId()
    {
        $expected = '12345';
        $this->checkoutSession->method('getPayoneMandate')->willReturn(['mandate_identification' => $expected]);

        $result = $this->classToTest->getMandateId();
        $this->assertEquals($expected, $result);
    }

    public function testGetMandateIdFalse()
    {
        $this->checkoutSession->method('getPayoneMandate')->willReturn(false);

        $result = $this->classToTest->getMandateId();
        $this->assertFalse($result);
    }

    public function testGetCheckoutUrl()
    {
        $expected = 'http://testdomain.com';
        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getCheckoutUrl();
        $this->assertEquals($expected, $result);
    }

    public function testGetErrorMessage()
    {
        $expected = 'An error occured';
        $this->checkoutSession->method('getPayoneDebitError')->willReturn($expected);

        $result = $this->classToTest->getErrorMessage();
        $this->assertEquals($expected, $result);
    }
}
