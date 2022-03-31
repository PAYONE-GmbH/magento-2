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

namespace Payone\Core\Test\Unit\Model\System\Config\Backend;

use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\System\Config\Backend\Upload as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\ApplePay;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;

class UploadTest extends BaseTestCase
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
     * @var ApplePay|\PHPUnit\Framework\MockObject\MockObject 
     */
    private $applePayHelper;

    /**
     * @var Read|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tmpDirectory;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->applePayHelper = $this->getMockBuilder(ApplePay::class)->disableOriginalConstructor()->getMock();
        
        $this->tmpDirectory = $this->getMockBuilder(Read::class)->disableOriginalConstructor()->getMock();
        
        $filesystem = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
        $filesystem->method('getDirectoryRead')->willReturn($this->tmpDirectory);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'applePayHelper' => $this->applePayHelper,
            'filesystem' => $filesystem
        ]);
    }

    public function testBeforeSave()
    {
        $uploadPath = __DIR__.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR;

        $this->applePayHelper->method('getApplePayUploadPath')->willReturn($uploadPath);
        $this->tmpDirectory->method('getRelativePath')->willReturn("Existing path");
        $this->tmpDirectory->method('isExist')->willReturn(true);
        $this->tmpDirectory->method('stat')->willReturn(['size' => 100]);

        $fileUpload = [
            'value' => 'test.file',
            'name' => 'uploaded.tmp',
            'tmp_name' => $uploadPath."upload.tmp",
        ];
        $this->classToTest->setValue($fileUpload);

        $result = $this->classToTest->beforeSave();
        $this->assertInstanceOf(ClassToTest::class, $result);

        rmdir($uploadPath);
    }

    public function testBeforeSaveException()
    {
        $uploadPath = __DIR__.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR;

        $this->applePayHelper->method('getApplePayUploadPath')->willReturn($uploadPath);
        $this->tmpDirectory->method('getRelativePath')->willReturn("Existing path");
        $this->tmpDirectory->method('isExist')->willReturn(true);
        $this->tmpDirectory->method('stat')->willReturn(['size' => false]);

        $fileUpload = [
            'value' => 'test.file',
            'name' => 'uploaded.tmp',
            'tmp_name' => $uploadPath."upload.tmp",
        ];
        $this->classToTest->setValue($fileUpload);

        $this->expectException(LocalizedException::class);
        $result = $this->classToTest->beforeSave();
    }

    public function testBeforeSaveEmpty()
    {
        $this->classToTest->setValue(['name' => 'uploaded.tmp']);

        $result = $this->classToTest->beforeSave();
        $this->assertInstanceOf(ClassToTest::class, $result);
    }

    public function testBeforeSaveDelete()
    {
        $currentPath = __DIR__.DIRECTORY_SEPARATOR;

        $this->applePayHelper->method('getApplePayUploadPath')->willReturn($currentPath);

        $deleteFile = "test.file";
        file_put_contents($currentPath.$deleteFile, "data");

        $fileUpload = [
            'value' => $deleteFile,
            'delete' => 1,
        ];
        $this->classToTest->setValue($fileUpload);

        $result = $this->classToTest->beforeSave();
        $this->assertInstanceOf(ClassToTest::class, $result);
        $this->assertTrue(!file_exists($currentPath.$deleteFile));
    }
}
