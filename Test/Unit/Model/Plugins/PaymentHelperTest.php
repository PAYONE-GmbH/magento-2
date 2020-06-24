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

use Payone\Core\Model\Methods\Klarna\Invoice;
use Payone\Core\Model\Methods\Creditcard;
use Payone\Core\Model\Plugins\PaymentHelper as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Payment\Model\Method\Factory;

class PaymentHelperTest extends BaseTestCase
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
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);

        $methodInstance = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        
        $methodFactory = $this->getMockBuilder(Factory::class)->disableOriginalConstructor()->getMock();
        $methodFactory->method('create')->willReturn($methodInstance);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'paymentMethodFactory' => $methodFactory
        ]);
    }

    public function testAroundRefund()
    {
        $subject = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($subject) {
            return true;
        };

        $code = "payone_klarna_invoice";

        $this->scopeConfig->method('getValue')->willReturn('\Classname');

        #$this->expectException(CouldNotSaveException::class);
        $result = $this->classToTest->aroundGetMethodInstance($subject, $proceed, $code);
        $this->assertInstanceOf(MethodInterface::class, $result);
    }

    public function testAroundRefundException()
    {
        $subject = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($subject) {
            return true;
        };

        $code = "payone_klarna_invoice";

        $this->scopeConfig->method('getValue')->willReturn(false);

        $this->expectException(\UnexpectedValueException::class);
        $this->classToTest->aroundGetMethodInstance($subject, $proceed, $code);
    }

    public function testAroundRefundNotKlarna()
    {
        $code = "payone_creditcard";
        $methodInstance = $this->getMockBuilder(Creditcard::class)->disableOriginalConstructor()->getMock();

        $subject = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($methodInstance) {
            return $methodInstance;
        };

        $result = $this->classToTest->aroundGetMethodInstance($subject, $proceed, $code);
        $this->assertEquals($methodInstance, $result);
    }
}
