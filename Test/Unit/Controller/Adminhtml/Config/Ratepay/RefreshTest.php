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

namespace Payone\Core\Test\Unit\Controller\Adminhtml\Config\Ratepay;

use Payone\Core\Controller\Adminhtml\Config\Ratepay\Refresh as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action\Context;
use Payone\Core\Helper\Ratepay;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\AuthorizationInterface;

class RefreshTest extends BaseTestCase
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
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Ratepay
     */
    private $ratepayHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $jsonResult = $this->getMockBuilder(Json::class)->disableOriginalConstructor()->getMock();
        $jsonResult->method('setData')->willReturn($jsonResult);

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)->disableOriginalConstructor()->getMock();
        $this->jsonFactory->method('create')->willReturn($jsonResult);

        $authorization = $this->getMockBuilder(AuthorizationInterface::class)->disableOriginalConstructor()->getMock();
        $authorization->method('isAllowed')->willReturn(true);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getAuthorization')->willReturn($authorization);

        $this->ratepayHelper = $this->getMockBuilder(Ratepay::class)->disableOriginalConstructor()->getMock();
        $this->ratepayHelper->method('getRequestParameter')->willReturn('payone_ratepay_invoice');
        
        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'ratepayHelper' => $this->ratepayHelper,
            'resultJsonFactory' => $this->jsonFactory,
        ]);
    }

    public function testExecute()
    {
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteError()
    {
        $exception = new LocalizedException(__('An error occured'));
        $this->ratepayHelper->method('refreshProfiles')->willThrowException($exception);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
}
