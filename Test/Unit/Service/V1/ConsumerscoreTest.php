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

use Payone\Core\Service\V1\Consumerscore as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Risk\Addresscheck;
use Payone\Core\Model\Api\Request\Consumerscore;
use Payone\Core\Helper\Consumerscore as ConsumerscoreHelper;
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Service\V1\Data\ConsumerscoreResponse;
use Payone\Core\Service\V1\Data\ConsumerscoreResponseFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ConsumerscoreTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Addresscheck|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addresscheck;

    /**
     * @var Consumerscore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consumerscore;

    /**
     * @var AddresscheckResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $this->consumerscore = $this->getMockBuilder(Consumerscore::class)->disableOriginalConstructor()->getMock();

        $consumerscoreHelper = $this->getMockBuilder(ConsumerscoreHelper::class)->disableOriginalConstructor()->getMock();
        $consumerscoreHelper->method('isCreditratingNeeded')->willReturn(true);

        $this->response = $objectManager->getObject(ConsumerscoreResponse::class);
        $responseFactory = $this->getMockBuilder(ConsumerscoreResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'addresscheck' => $this->addresscheck,
            'responseFactory' => $responseFactory,
            'consumerscore' => $this->consumerscore,
            'consumerscoreHelper' => $consumerscoreHelper
        ]);
    }

    public function testExecuteConsumerscorePrechecked()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $this->consumerscore->method('sendRequest')->willReturn(true);

        $result = $this->classToTest->executeConsumerscore($addressData, true, false, 100, 'test');
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testExecuteConsumerscoreInvalid()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'INVALID', 'customermessage' => 'invalid']);

        $result = $this->classToTest->executeConsumerscore($addressData, true, false, 100, 'test');
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
    }

    public function testExecuteConsumerscoreErrorStopCheckout()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'ERROR', 'customermessage' => 'error']);
        $this->addresscheck->method('getConfigParam')->willReturn('stop_checkout');

        $result = $this->classToTest->executeConsumerscore($addressData, false, false, 100, 'test');
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
    }

    public function testExecuteConsumerscoreErrorContinueCheckout()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'ERROR', 'customermessage' => 'error']);
        $this->addresscheck->method('getConfigParam')->willReturn('continue_checkout');

        $result = $this->classToTest->executeConsumerscore($addressData, false, false, 100, 'test');
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testExecuteConsumerscoreValid()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $addressData->method('getFirstname')->willReturn('Paul');
        $addressData->method('getLastname')->willReturn('Payer');
        $addressData->method('getStreet')->willReturn('Teststr. 1');
        $addressData->method('getPostcode')->willReturn('12345');
        $addressData->method('getCity')->willReturn('Test');

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID']);
        $this->addresscheck->method('correctAddress')->willReturn($addressData);
        $this->addresscheck->method('isAddressCorrected')->willReturn(true);

        $result = $this->classToTest->executeConsumerscore($addressData, false, false, 100, 'test');
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testExecuteConsumerscoreValidArrayStreet()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $addressData->method('getFirstname')->willReturn('Paul');
        $addressData->method('getLastname')->willReturn('Payer');
        $addressData->method('getStreet')->willReturn(['Teststr. 1']);
        $addressData->method('getPostcode')->willReturn('12345');
        $addressData->method('getCity')->willReturn('Test');

        $this->consumerscore->method('sendRequest')->willReturn(['status' => 'VALID']);
        $this->addresscheck->method('correctAddress')->willReturn($addressData);
        $this->addresscheck->method('isAddressCorrected')->willReturn(true);

        $result = $this->classToTest->executeConsumerscore($addressData, false, false, 100, 'test');
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }
}
