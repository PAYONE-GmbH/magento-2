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

namespace Payone\Core\Test\Unit\Controller\Adminhtml\Config\Amazon;

use Payone\Core\Controller\Adminhtml\Config\Amazon\Configuration as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action\Context;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\AuthorizationInterface;

class ConfigurationTest extends BaseTestCase
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
     * @var GetConfiguration
     */
    private $getConfiguration;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->getConfiguration = $this->getMockBuilder(GetConfiguration::class)->disableOriginalConstructor()->getMock();

        $jsonResult = $this->getMockBuilder(Json::class)->disableOriginalConstructor()->getMock();
        $jsonResult->method('setData')->willReturn($jsonResult);

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)->disableOriginalConstructor()->getMock();
        $this->jsonFactory->method('create')->willReturn($jsonResult);

        $authorization = $this->getMockBuilder(AuthorizationInterface::class)->disableOriginalConstructor()->getMock();
        $authorization->method('isAllowed')->willReturn(true);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getAuthorization')->willReturn($authorization);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'getConfiguration' => $this->getConfiguration,
            'resultJsonFactory' => $this->jsonFactory,
        ]);
    }

    public function testExecute()
    {
        $aResult = [
            'status' => 'OK',
            'add_paydata[client_id]' => '123',
            'add_paydata[seller_id]' => '123',
        ];

        $this->getConfiguration->method('sendRequest')->willReturn($aResult);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteError()
    {
        $aResult = [
            'status' => 'ERROR',
            'errormessage' => 'test',
        ];

        $this->getConfiguration->method('sendRequest')->willReturn($aResult);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
}
