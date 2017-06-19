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

namespace Payone\Core\Test\Unit\Model\Methods;

use Payone\Core\Model\Methods\Paydirekt as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\PayoneConfig;

class PayoneMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Shop|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shopHelper;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper
        ]);
    }

    public function testGetClearingtype()
    {
        $result = $this->classToTest->getClearingtype();
        $expected = 'wlt';
        $this->assertEquals($expected, $result);
    }

    public function testGetAuthorizationMode()
    {
        $expected = 'custom_request_type';
        $this->shopHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap(
                [
                    ['request_type', 'global', 'payone_general', null, 'global_request_type'],
                    ['use_global', PayoneConfig::METHOD_PAYDIREKT, 'payone_payment', null, '0'],
                    ['request_type', PayoneConfig::METHOD_PAYDIREKT, 'payone_payment', null, $expected]
                ]
            );
        $result = $this->classToTest->getAuthorizationMode();
        $this->assertEquals($expected, $result);
    }

    public function testGetOperationMode()
    {
        $expected = 'operation_mode';
        $this->shopHelper->method('getConfigParam')->willReturn($expected);
        $result = $this->classToTest->getOperationMode();
        $this->assertEquals($expected, $result);
    }

    public function testNeedsRedirectUrls()
    {
        $result = $this->classToTest->needsRedirectUrls();
        $this->assertTrue($result);
    }

    public function testNeedsProductInfo()
    {
        $result = $this->classToTest->needsProductInfo();
        $this->assertFalse($result);
    }

    public function testHasCustomConfig()
    {
        $this->shopHelper->method('getConfigParam')->willReturn('1');
        $result = $this->classToTest->hasCustomConfig();
        $this->assertFalse($result);
    }

    public function testIsGroupMethod()
    {
        $result = $this->classToTest->isGroupMethod();
        $this->assertFalse($result);
    }

    public function testGetGroupName()
    {
        $result = $this->classToTest->getGroupName();
        $this->assertFalse($result);
    }

    public function testGetNarrativeTextMaxLength()
    {
        $result = $this->classToTest->getNarrativeTextMaxLength();
        $expected = 37;
        $this->assertEquals($expected, $result);
    }
}
