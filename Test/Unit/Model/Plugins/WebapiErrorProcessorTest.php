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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Plugins;

use Payone\Core\Model\Plugins\WebapiErrorProcessor as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Webapi\ErrorProcessor;
use Payone\Core\Model\Exception\FilterMethodListException;

class WebapiErrorProcessorTest extends BaseTestCase
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

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);
    }

    public function testBeforeMaskException()
    {
        $subject = $this->getMockBuilder(ErrorProcessor::class)->disableOriginalConstructor()->getMock();

        $exception = $this->getMockBuilder(\Exception::class)->disableOriginalConstructor()->getMock();
        $exception->method('getPrevious')->willReturn(false);

        $result = $this->classToTest->beforeMaskException($subject, $exception);

        $this->assertNull($result);
    }

    public function testBeforeMaskExceptionEmpty()
    {
        $subject = $this->getMockBuilder(ErrorProcessor::class)->disableOriginalConstructor()->getMock();
        $previous = $this->getMockBuilder(FilterMethodListException::class)->disableOriginalConstructor()->getMock();

        $exception = new \Exception("Test", 0, $previous);

        $result = $this->classToTest->beforeMaskException($subject, $exception);
        $this->assertEquals([$previous], $result);
    }
}
