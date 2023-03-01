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

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\ApplePay as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;

class ApplePayTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $read = $this->getMockBuilder(Read::class)->disableOriginalConstructor()->getMock();
        $read->method('getAbsolutePath')->willReturn("Upload Path");

        $filesystem = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
        $filesystem->method('getDirectoryRead')->willReturn($read);
        
        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'filesystem' => $filesystem,
        ]);
    }

    public function testGetApplePayUploadPath()
    {
        $result = $this->classToTest->getApplePayUploadPath();

        $this->assertNotEmpty($result);
    }

    public function testHasMerchantId()
    {
        $this->scopeConfig->method('getValue')->willReturn("merchantId");

        $result = $this->classToTest->hasMerchantId();
        $this->assertTrue($result);
    }

    public function testHasMerchantIdFalse()
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $result = $this->classToTest->hasMerchantId();
        $this->assertFalse($result);

        $result = $this->classToTest->isConfigurationComplete();
        $this->assertFalse($result);
    }

    public function testHasFiles()
    {
        $testFile = "unit.test";
        $this->scopeConfig->method('getValue')->willReturn($testFile);

        $path = $this->classToTest->getApplePayUploadPath().$testFile;
        file_put_contents($path, "test");

        $result = $this->classToTest->hasCertificateFile();
        $this->assertTrue($result);

        $result = $this->classToTest->hasPrivateKeyFile();
        $this->assertTrue($result);

        $result = $this->classToTest->isConfigurationComplete();
        $this->assertTrue($result);

        unlink($path);
    }

    public function testHasPrivateKeyFileFalse()
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $result = $this->classToTest->hasPrivateKeyFile();
        $this->assertFalse($result);
    }

    public function testIsConfigurationComplete()
    {
        $this->scopeConfig->method('getValue')->willReturn("not.existing");

        $result = $this->classToTest->isConfigurationComplete();
        $this->assertFalse($result);
    }
}
