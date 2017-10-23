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

namespace Payone\Core\Test\Unit\Model\TransactionStatus;

use Payone\Core\Model\TransactionStatus\Forwarding as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ForwardingTest extends BaseTestCase
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

        $forwarding = [['txaction' => ['appointed', 'paid'], 'url' => 'http://testdomain.com', 'timeout' => 0]];

        $configHelper = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $configHelper->method('getForwardingUrls')->willReturn($forwarding);

        $curl = $this->getMockBuilder(Curl::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'configHelper' => $configHelper,
            'curl' => $curl
        ]);
    }

    public function testHandleForwardings()
    {
        $post = ['txid' => '12345', 'txaction' => 'appointed'];

        $result = $this->classToTest->handleForwardings($post);
        $this->assertNull($result);
    }
}
