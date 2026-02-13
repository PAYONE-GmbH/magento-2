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
 * PHP version 8
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2026 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Service\V1\Data;

use Payone\Core\Model\Api\Request\GetJWT;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Payone\Core\Service\V1\ClickToPay as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Service\V1\Data\ClickToPayResponse;
use Payone\Core\Api\Data\ClickToPayResponseInterfaceFactory;
use Magento\Checkout\Model\Session;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class ClickToPayTest extends BaseTestCase
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
     * @var GetJWT|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jwtRequest;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSession;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(ClickToPayResponse::class);
        $responseFactory = $this->getMockBuilder(ClickToPayResponseInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'save',
                'getPayment',
                'setPayment',
            ])
            ->getMock();
        $quote->method('getPayment')->willReturn($payment);

        $this->checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->jwtRequest = $this->getMockBuilder(GetJWT::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'checkoutSession' => $this->checkoutSession,
            'jwtRequest' => $this->jwtRequest,
        ]);
    }

    public function testGetJwt()
    {
        $this->jwtRequest->method('sendRequest')->willReturn([
            'status' => 'ok',
            'token' => '12345',
        ]);

        $result = $this->classToTest->getJwt(4711);

        $this->assertInstanceOf(ClickToPayResponse::class, $result);
        $this->assertTrue($result->getSuccess());
    }

    public function testGetJwtError()
    {
        $this->jwtRequest->method('sendRequest')->willReturn([
            'status' => 'ERROR',
            'customermessage' => 'ERROR',
        ]);

        $result = $this->classToTest->getJwt(4711);

        $this->assertInstanceOf(ClickToPayResponse::class, $result);
        $this->assertFalse($result->getSuccess());
    }
}
