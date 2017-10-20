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

namespace Payone\Core\Test\Unit\Service\V1\Data;

use Payone\Core\Service\V1\Addresscheck as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Risk\Addresscheck;
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Service\V1\Data\AddresscheckResponse;
use Payone\Core\Service\V1\Data\AddresscheckResponseFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class AddresscheckTest extends BaseTestCase
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
     * @var AddresscheckResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $this->response = $objectManager->getObject(AddresscheckResponse::class);
        $responseFactory = $this->getMockBuilder(AddresscheckResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'addresscheck' => $this->addresscheck,
            'responseFactory' => $responseFactory
        ]);
    }

    public function testCheckAddressFalse()
    {
        $this->addresscheck->method('isCheckNeededForQuote')->willReturn(false);

        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->checkAddress($addressData, false, false, 100);
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
    }

    public function testCheckAddressTrue()
    {
        $this->addresscheck->method('isCheckNeededForQuote')->willReturn(true);
        $this->addresscheck->method('getResponse')->willReturn(true);

        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->checkAddress($addressData, false, false, 100);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testCheckAddressInvalid()
    {
        $expected = 'invalid message';
        $this->addresscheck->method('isCheckNeededForQuote')->willReturn(true);
        $this->addresscheck->method('getResponse')->willReturn(['status' => 'INVALID', 'customermessage' => $expected]);
        $this->addresscheck->method('getScore')->willReturn('G');
        $this->addresscheck->method('getInvalidMessage')->willReturn($expected);

        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->checkAddress($addressData, false, false, 100);
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
        $this->assertEquals($expected, $result['errormessage']);
    }

    public function testCheckAddressErrorContinue()
    {
        $expected = 'invalid message';
        $this->addresscheck->method('isCheckNeededForQuote')->willReturn(true);
        $this->addresscheck->method('getResponse')->willReturn(['status' => 'ERROR']);
        $this->addresscheck->method('getScore')->willReturn('G');
        $this->addresscheck->method('getConfigParam')->willReturn('continue_checkout');

        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->checkAddress($addressData, true, false, 100);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testCheckAddressErrorStop()
    {
        $expected = 'error message';
        $this->addresscheck->method('isCheckNeededForQuote')->willReturn(true);
        $this->addresscheck->method('getResponse')->willReturn(['status' => 'ERROR']);
        $this->addresscheck->method('getScore')->willReturn('G');
        $this->addresscheck->method('getConfigParam')->willReturn('stop_checkout');
        $this->addresscheck->method('getErrorMessage')->willReturn($expected);

        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->checkAddress($addressData, true, false, 100);
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
        $this->assertEquals($expected, $result['errormessage']);
    }

    public function testCheckAddressValid()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $this->addresscheck->method('isCheckNeededForQuote')->willReturn(true);
        $this->addresscheck->method('getResponse')->willReturn(['status' => 'VALID']);
        $this->addresscheck->method('getScore')->willReturn('G');
        $this->addresscheck->method('isAddressCorrected')->willReturn(true);
        $this->addresscheck->method('correctAddress')->willReturn($addressData);

        $result = $this->classToTest->checkAddress($addressData, true, false, 100);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['confirmMessage']);
    }

    public function testCheckAddressValidStreetArray()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $addressData->method('getStreet')->willReturn(['Teststr. 1', 'Additional info']);

        $this->addresscheck->method('isCheckNeededForQuote')->willReturn(true);
        $this->addresscheck->method('getResponse')->willReturn(['status' => 'VALID']);
        $this->addresscheck->method('getScore')->willReturn('G');
        $this->addresscheck->method('isAddressCorrected')->willReturn(true);
        $this->addresscheck->method('correctAddress')->willReturn($addressData);

        $result = $this->classToTest->checkAddress($addressData, true, false, 100);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['confirmMessage']);
    }
}
