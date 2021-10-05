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
use Payone\Core\Helper\Shop;
use Payone\Core\Helper\ApplePay;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\HTTP\Client\Curl;

class ForwardingTest extends BaseTestCase
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

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'applePayHelper' => $this->applePayHelper,
            'curl' => $this->curl,
        ]);
    }

    public function testGetApplePaySession()
    {
        $expected = "ApplePay Session";

        $this->shopHelper->method("getStoreBaseUrl")->willReturn("https://www.testshop.net");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(true);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(true);
        $this->curl->method("getBody")->willReturn($expected);

        $result = $this->classToTest->getApplePaySession();
        $this->assertEquals($expected, $result);
    }

    public function testGetApplePaySessionCertificateException()
    {
        $this->shopHelper->method("getStoreBaseUrl")->willReturn("https://www.testshop.net");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(false);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(true);

        $this->expectException(\Exception::class);
        $result = $this->classToTest->getApplePaySession();
    }

    public function testGetApplePaySessionPrivateKeyException()
    {
        $this->shopHelper->method("getStoreBaseUrl")->willReturn("no url");
        $this->applePayHelper->method("hasCertificateFile")->willReturn(true);
        $this->applePayHelper->method("hasPrivateKeyFile")->willReturn(false);

        $this->expectException(\Exception::class);
        $result = $this->classToTest->getApplePaySession();
    }
}
