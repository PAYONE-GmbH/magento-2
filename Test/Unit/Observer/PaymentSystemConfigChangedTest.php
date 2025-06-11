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

namespace Payone\Core\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Payone\Core\Helper\Ratepay;
use Payone\Core\Observer\PaymentSystemConfigChanged as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\Info;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class PaymentSystemConfigChangedTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $ratepayHelper = $this->getMockBuilder(Ratepay::class)->disableOriginalConstructor()->getMock();
        $ratepayHelper->method('getPaymentMethodFromPath')->willReturn('payone_ratepay_invoice');
        $ratepayHelper->method('getRatepayShopConfigByPath')->willReturn([['shop_id' => '12345', 'currency' => 'EUR']]);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'ratepayHelper' => $ratepayHelper
        ]);
    }

    public function testExecute()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getChangedPaths'])
            ->getMock();
        $observer->method('getChangedPaths')->willReturn(['payone_payment/ratepay_invoice/ratepay_shop_config']);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }
}
