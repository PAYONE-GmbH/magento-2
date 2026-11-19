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

namespace Payone\Core\Test\Unit\Controller\Mandate;

use Payone\Core\Controller\Payolution\DraftDownload as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Checkout\Model\Session;
use Payone\Core\Helper\Payment;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

#[AllowMockObjectsWithoutExpectations]
class DraftDownloadTest extends BaseTestCase
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
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var Curl|\PHPUnit_Framework_MockObject_MockObject
     */
    private $curl;

    /**
     * @var Raw|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rawResponse;

    /**
     * @var string
     */
    private $duration = '5';

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $aLinks = ['5' => 'https://drafturl.com', '6' => 'http://drafturl.com'];

        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['__call'])->getMock();
        $checkoutSession->method('__call')->willReturnMap([
            ['getInstallmentDraftLinks', [], $aLinks],
        ]);

        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $this->rawResponse = $this->getMockBuilder(Raw::class)->disableOriginalConstructor()->getMock();
        $resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $resultRawFactory->method('create')->willReturn($this->rawResponse);

        $request = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $request->method('getParam')->willReturnCallback(function () {
            return $this->duration;
        });

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($request);

        $this->curl = $this->getMockBuilder(Curl::class)->disableOriginalConstructor()->getMock();
        $curlFactory = $this->getMockBuilder(CurlFactory::class)->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $curlFactory->method('create')->willReturn($this->curl);
        $this->curl->method('getBody')->willReturn('Pdf Data');

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $checkoutSession,
            'paymentHelper' => $this->paymentHelper,
            'resultRawFactory' => $resultRawFactory,
            'curlFactory' => $curlFactory
        ]);
    }

    public function testExecute()
    {
        $this->paymentHelper->method('getConfigParam')->willReturn('value');
        $this->curl->expects($this->once())->method('setCredentials')->with('value', 'value');
        $this->curl->expects($this->once())->method('get')->with('https://drafturl.com');
        $this->rawResponse->expects($this->atLeastOnce())->method('setHeader');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    public function testExecuteNoHttps()
    {
        $this->duration = '6';
        $this->paymentHelper->method('getConfigParam')->willReturn('value');
        $this->curl->expects($this->never())->method('setCredentials');
        $this->curl->expects($this->never())->method('get');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    public function testExecuteConnectionError()
    {
        $this->paymentHelper->method('getConfigParam')->willReturn('value');
        $this->curl->method('get')->willThrowException(new \Exception('SSL certificate problem'));
        $this->rawResponse->expects($this->never())->method('setHeader');

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    public function testExecuteNoDraft()
    {
        $this->paymentHelper->method('getConfigParam')->willReturn(false);

        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }
}
