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

namespace Payone\Core\Test\Unit\Service\V1\Data;

use Payone\Core\Model\ApplePay\SessionHandler;
use Payone\Core\Service\V1\ApplePay as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Service\V1\Data\ApplePayResponse;
use Payone\Core\Service\V1\Data\ApplePayResponseFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ApplePayTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ApplePayResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * @var SessionHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sessionHandler;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(ApplePayResponse::class);
        $responseFactory = $this->getMockBuilder(ApplePayResponseFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $this->sessionHandler = $this->getMockBuilder(SessionHandler::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'sessionHandler' => $this->sessionHandler,
        ]);
    }

    public function testGetApplePaySession()
    {
        $this->sessionHandler->method('getApplePaySession')->willReturn("ApplePay Session");

        $result = $this->classToTest->getApplePaySession(4711);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testGetApplePaySessionException()
    {
        $this->sessionHandler->method('getApplePaySession')->willThrowException(new \Exception("Error"));

        $result = $this->classToTest->getApplePaySession(4711);
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
    }
}
