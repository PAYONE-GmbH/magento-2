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

namespace Payone\Core\Test\Unit\Helper\Connection;

use Payone\Core\Helper\Connection\CurlCli;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class CurlCliTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var CurlCli
     */
    private $curlCli;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->curlCli = $this->objectManager->getObject(CurlCli::class);
    }

    public function testIsApplicable()
    {
        $result = $this->curlCli->isApplicable();
        $expected = (file_exists("/usr/local/bin/curl") || file_exists("/usr/bin/curl"));
        $this->assertEquals($expected, $result);
    }
}
