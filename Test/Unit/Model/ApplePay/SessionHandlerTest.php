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
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\ApplePay;

use Payone\Core\Model\ApplePay\SessionHandler as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Payone\Core\Helper\Shop;
use Payone\Core\Helper\ApplePay;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;

#[AllowMockObjectsWithoutExpectations]
class SessionHandlerTest extends BaseTestCase
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
     * @var Curl|\PHPUnit\Framework\MockObject\MockObject
     */
    private $curl;

    /**
     * @var Shop|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shopHelper;

    /**
     * @var ApplePay|\PHPUnit\Framework\MockObject\MockObject
     */
    private $applePayHelper;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->shopHelper->method("getConfigParam")->willReturn("value");

        $this->applePayHelper = $this->getMockBuilder(ApplePay::class)->disableOriginalConstructor()->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)->disableOriginalConstructor()->getMock();
        $curlFactory = $this->getMockBuilder(CurlFactory::class)->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $curlFactory->method('create')->willReturn($this->curl);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'applePayHelper' => $this->applePayHelper,
            'curlFactory' => $curlFactory,
        ]);
    }

    public function testGetApplePaySession()
    {
        $expected = "ApplePay Session";

        $this->shopHelper->method("getStoreBaseUrl")->willReturn("https://www.testshop.net");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(true);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(true);
        $this->curl->method("getBody")->willReturn($expected);

        $result = $this->classToTest->getApplePaySession('https://apple-pay-gateway.apple.com/paymentservices/startSession');
        $this->assertEquals($expected, $result);
    }

    public function testGetApplePaySessionWithoutValidationUrl()
    {
        $this->shopHelper->method("getStoreBaseUrl")->willReturn("https://www.testshop.net");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(true);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(true);
        $this->curl->method("getBody")->willReturn("ApplePay Session");

        $this->curl->expects($this->once())
            ->method("post")
            ->with('https://apple-pay-gateway.apple.com/paymentservices/startSession', $this->anything());

        $result = $this->classToTest->getApplePaySession();
        $this->assertEquals("ApplePay Session", $result);
    }

    public function testGetApplePaySessionSandboxValidationUrl()
    {
        $sValidationUrl = 'https://apple-pay-gateway-cert.apple.com/paymentservices/startSession';

        $this->shopHelper->method("getStoreBaseUrl")->willReturn("https://www.testshop.net");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(true);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(true);

        $this->curl->expects($this->once())
            ->method("post")
            ->with($sValidationUrl, $this->anything());

        $this->classToTest->getApplePaySession($sValidationUrl);
    }

    public function testGetApplePaySessionHttpSchemeIsRejected()
    {
        $this->curl->expects($this->never())->method("post");

        $this->expectException(\Exception::class);
        $this->classToTest->getApplePaySession('http://apple-pay-gateway.apple.com/paymentservices/startSession');
    }

    public function testGetApplePaySessionForeignHostIsRejected()
    {
        $this->curl->expects($this->never())->method("post");

        $this->expectException(\Exception::class);
        $this->classToTest->getApplePaySession('https://www.example.com/paymentservices/startSession');
    }

    public function testGetApplePaySessionHostWithAppleSuffixIsRejected()
    {
        $this->curl->expects($this->never())->method("post");

        $this->expectException(\Exception::class);
        $this->classToTest->getApplePaySession('https://apple-pay-gateway.apple.com.example.com/paymentservices/startSession');
    }

    public function testGetApplePaySessionHostWithApplePrefixIsRejected()
    {
        $this->curl->expects($this->never())->method("post");

        $this->expectException(\Exception::class);
        $this->classToTest->getApplePaySession('https://example-apple-pay-gateway.apple.com/paymentservices/startSession');
    }

    public function testGetApplePaySessionDerCertificate()
    {
        $aOptions = $this->getCurlOptionsForCertificate("\x30\x82\x05\xd1\x30\x82\x04\xb9");

        $this->assertArrayHasKey(CURLOPT_SSLCERTTYPE, $aOptions);
        $this->assertEquals('DER', $aOptions[CURLOPT_SSLCERTTYPE]);
    }

    public function testGetApplePaySessionPemCertificate()
    {
        $aOptions = $this->getCurlOptionsForCertificate("-----BEGIN CERTIFICATE-----\nMIIF0TCCBLmgAwIBAgIIZ\n-----END CERTIFICATE-----\n");

        $this->assertArrayNotHasKey(CURLOPT_SSLCERTTYPE, $aOptions);
    }

    /**
     * Requests a session with the given certificate file content and returns the curl options that were set
     *
     * @param  string $sCertificateContent
     * @return array
     */
    private function getCurlOptionsForCertificate($sCertificateContent)
    {
        $sUploadDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('payone_applepay_').DIRECTORY_SEPARATOR;
        mkdir($sUploadDir);
        $sCertFile = $sUploadDir."value"; // getConfigParam returns "value" for the certificate file name
        file_put_contents($sCertFile, $sCertificateContent);

        $this->shopHelper->method("getStoreBaseUrl")->willReturn("https://www.testshop.net");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(true);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(true);
        $this->applePayHelper->method("getApplePayUploadPath")->willReturn($sUploadDir);

        $aOptions = [];
        $this->curl->method("setOption")->willReturnCallback(function ($iOption, $mValue) use (&$aOptions) {
            $aOptions[$iOption] = $mValue;
        });

        try {
            $this->classToTest->getApplePaySession('https://apple-pay-gateway.apple.com/paymentservices/startSession');
        } finally {
            unlink($sCertFile);
            rmdir($sUploadDir);
        }
        return $aOptions;
    }

    public function testGetApplePaySessionCertificateException()
    {
        $this->shopHelper->method("getStoreBaseUrl")->willReturn("https://www.testshop.net");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(false);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(true);

        $this->expectException(\Exception::class);
        $result = $this->classToTest->getApplePaySession('https://apple-pay-gateway.apple.com/paymentservices/startSession');
    }

    public function testGetApplePaySessionPrivateKeyException()
    {
        $this->shopHelper->method("getStoreBaseUrl")->willReturn("no url");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(true);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(false);

        $this->expectException(\Exception::class);
        $result = $this->classToTest->getApplePaySession('https://apple-pay-gateway.apple.com/paymentservices/startSession');
    }
}
