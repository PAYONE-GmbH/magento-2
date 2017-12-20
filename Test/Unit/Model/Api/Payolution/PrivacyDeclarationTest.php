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

namespace Payone\Core\Test\Unit\Model\Api\Request\Genericpayment;

use Payone\Core\Model\Api\Payolution\PrivacyDeclaration as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Magento\Framework\HTTP\Client\Curl;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class PrivacyDeclarationTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    /**
     * @var Curl|\PHPUnit_Framework_MockObject_MockObject
     */
    private $curl;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper->method('getLocale')->willReturn('de');

        $this->curl = $this->getMockBuilder(Curl::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'curl' => $this->curl
        ]);
    }

    public function testGetPayolutionAcceptanceText()
    {
        $this->shopHelper->method('getConfigParam')->willReturn(true);
        $this->curl->method('getBody')->willReturn('<body>Weg damit<header>payolution</header>Test</body>');

        $result = $this->classToTest->getPayolutionAcceptanceText('payone_paymentcode');
        $expected = '<header>payolution</header>Test';
        $this->assertEquals($expected, $result);
    }

    public function testGetPayolutionAcceptanceTextFallback()
    {
        $this->shopHelper->method('getConfigParam')->willReturn(true);
        $this->curl->method('getBody')->willReturn(false);

        $result = $this->classToTest->getPayolutionAcceptanceText('payone_paymentcode');
        $this->assertNotEmpty($result);
    }

    public function testGetPayolutionAcceptanceTextNotActive()
    {
        $this->shopHelper->method('getConfigParam')->willReturn(false);

        $result = $this->classToTest->getPayolutionAcceptanceText('payone_paymentcode');
        $expected = false;
        $this->assertEquals($expected, $result);
    }
}
