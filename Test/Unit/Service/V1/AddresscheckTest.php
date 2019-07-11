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
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Service\V1\Data\AddresscheckResponse;
use Payone\Core\Service\V1\Data\AddresscheckResponseFactory;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Model\SimpleProtect\SimpleProtect;
use Magento\Framework\Exception\LocalizedException;

class AddresscheckTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var AddresscheckResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * PAYONE Simple Protect implementation
     *
     * @var SimpleProtect|\PHPUnit_Framework_MockObject_MockObject
     */
    private $simpleProtect;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->response = $objectManager->getObject(AddresscheckResponse::class);
        $responseFactory = $this->getMockBuilder(AddresscheckResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $responseFactory->method('create')->willReturn($this->response);

        $this->simpleProtect = $this->getMockBuilder(SimpleProtect::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'responseFactory' => $responseFactory,
            'simpleProtect' => $this->simpleProtect
        ]);
    }

    public function testCheckAddressBilling()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $this->simpleProtect->method('handleEnterOrChangeBillingAddress')->willReturn($addressData);

        $result = $this->classToTest->checkAddress($addressData, true, false, 100);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testCheckAddressShipping()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $this->simpleProtect->method('handleEnterOrChangeShippingAddress')->willReturn($addressData);

        $result = $this->classToTest->checkAddress($addressData, false, false, 100);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testCheckAddressShippingStreetArray()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();
        $addressData->method('getStreet')->willReturn(['Teststr. 1', 'Additional info']);

        $this->simpleProtect->method('handleEnterOrChangeShippingAddress')->willReturn($addressData);

        $result = $this->classToTest->checkAddress($addressData, false, false, 100);
        $result = $result->__toArray();
        $this->assertTrue($result['success']);
    }

    public function testCheckAddressException()
    {
        $addressData = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $exception = new LocalizedException(__('Error'));

        $this->simpleProtect->method('handleEnterOrChangeShippingAddress')->willThrowException($exception);

        $result = $this->classToTest->checkAddress($addressData, false, false, 100);
        $result = $result->__toArray();
        $this->assertFalse($result['success']);
    }
}
