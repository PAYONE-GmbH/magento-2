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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\Mail as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;

class MailTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $transport = $this->getMockBuilder(TransportInterface::class)->disableOriginalConstructor()->getMock();

        $transportBuilder = $this->getMockBuilder(TransportBuilder::class)->disableOriginalConstructor()->getMock();
        $transportBuilder->method('setTemplateIdentifier')->willReturn($transportBuilder);
        $transportBuilder->method('setTemplateOptions')->willReturn($transportBuilder);
        $transportBuilder->method('setTemplateVars')->willReturn($transportBuilder);
        $transportBuilder->method('setFrom')->willReturn($transportBuilder);
        $transportBuilder->method('addTo')->willReturn($transportBuilder);
        $transportBuilder->method('getTransport')->willReturn($transport);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'transportBuilder' => $transportBuilder,
            'storeManager' => $storeManager
        ]);
    }

    public function testSendEmail()
    {
        $this->classToTest->sendEmail('test@test.com', 'template');
        $this->assertTrue(true);
    }
}
