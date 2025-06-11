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
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Creditmemo;
use Payone\Core\Observer\CreditmemoRegisterBefore as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Payment\Model\Info;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class CreditmemoRegisterBeforeTest extends BaseTestCase
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

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);
    }

    public function testExecute()
    {
        $creditmemo = $this->getMockBuilder(Creditmemo::class)->disableOriginalConstructor()->getMock();
        
        $input = [
            'payone_iban' => 'IBAN',
            'payone_bic' => 'BIC',
        ];

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getInput', 'getCreditmemo'])
            ->getMock();
        $event->method('getInput')->willReturn($input);
        $event->method('getCreditmemo')->willReturn($creditmemo);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }
}
