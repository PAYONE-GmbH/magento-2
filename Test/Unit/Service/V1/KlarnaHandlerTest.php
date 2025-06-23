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

namespace Payone\Core\Test\Unit\Service\V1\Data;

use Payone\Core\Model\Api\Request\Genericpayment\StartSession;
use Payone\Core\Model\Methods\Klarna\Invoice;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Service\V1\KlarnaHandler as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Payone\Core\Service\V1\Data\KlarnaHandlerResponse;
use Payone\Core\Service\V1\Data\KlarnaHandlerResponseFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Payment\Helper\Data;
use Magento\Customer\Model\Address;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

class KlarnaHandlerTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var KlarnaHandlerResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * @var StartSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $startSession;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(KlarnaHandlerResponse::class);
        $responseFactory = $this->getMockBuilder(KlarnaHandlerResponseFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $methodInstance = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();

        $dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataHelper->method('getMethodInstance')->willReturn($methodInstance);

        $this->startSession = $this->getMockBuilder(StartSession::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);


        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'dataHelper' => $dataHelper,
            'startSession' => $this->startSession,
            'checkoutSession' => $checkoutSession,
        ]);
    }

    public function testStartKlarnaSession()
    {
        $response = [
            'status' => 'OK',
            'add_paydata[client_token]' => 'Klarna Token'
        ];

        $this->startSession->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->startKlarnaSession(4711, PayoneConfig::METHOD_KLARNA_INVOICE, 5, 'tester@payone.de');
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testStartKlarnaSessionError()
    {
        $response = [
            'status' => 'ERROR',
            'errorcode' => '999',
            'customermessage' => 'An error occured'
        ];

        $this->startSession->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->startKlarnaSession(4711, PayoneConfig::METHOD_KLARNA_INVOICE, 5, 'tester@payone.de');
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
    }

    public function testStartKlarnaSessionAnotherError()
    {
        $response = [
            'status' => 'ERROR',
            'errorcode' => '981',
            'customermessage' => 'An error occured'
        ];

        $this->startSession->method('sendRequest')->willReturn($response);

        $result = $this->classToTest->startKlarnaSession(4711, PayoneConfig::METHOD_KLARNA_INVOICE, 5, 'tester@payone.de');
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
    }
}
