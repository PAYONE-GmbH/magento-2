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
 * PHP version 7
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Plugins;

use Magento\Quote\Model\Quote;
use Payone\Core\Helper\Checkout;
use Payone\Core\Model\Plugins\GuestCheckoutLayoutProcessor as ClassToTest;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Address;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Quote\Api\Data\AddressExtension;
use Magento\Customer\Model\Metadata\CustomerMetadata;
use Magento\Customer\Model\Data\Option;
use Magento\Customer\Model\Data\AttributeMetadata;
use Magento\Checkout\Model\Session;

class GuestCheckoutLayoutProcessorTest extends BaseTestCase
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
     * @var Checkout|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutHelper;

    /**
     * @var CustomerMetadata|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerMetaData;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();

        $this->customerMetaData = $this->getMockBuilder(CustomerMetadata::class)->disableOriginalConstructor()->getMock();
        
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        
        $checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutHelper' => $this->checkoutHelper,
            'customerMetaData' => $this->customerMetaData,
            'checkoutSession' => $checkoutSession
        ]);
    }

    public function testAfterProcessCreditrating()
    {
        $option = $this->getMockBuilder(Option::class)->disableOriginalConstructor()->getMock();
        $option->method('getLabel')->willReturn('label');
        $option->method('getValue')->willReturn('value');

        $attributeMetadata = $this->getMockBuilder(AttributeMetadata::class)->disableOriginalConstructor()->getMock();
        $attributeMetadata->method('getOptions')->willReturn([$option]);
        $attributeMetadata->method('isVisible')->willReturn(true);

        $this->customerMetaData->method('getAttributeMetadata')->willReturn($attributeMetadata);

        $this->checkoutHelper->method('getCurrentCheckoutMethod')->willReturn('guest');
        $this->checkoutHelper->method('getConfigParam')->willReturn('CE');

        $subject = $this->getMockBuilder(\Magento\Checkout\Block\Checkout\LayoutProcessor::class)->disableOriginalConstructor()->getMock();

        $inputArray = ['key' => 'value'];

        $result = $this->classToTest->afterProcess($subject, $inputArray);
        $this->assertCount(2, $result);
    }

    public function testAfterProcessCreditratingException()
    {
        $option = $this->getMockBuilder(Option::class)->disableOriginalConstructor()->getMock();
        $option->method('getLabel')->willReturn('label');
        $option->method('getValue')->willReturn('value');

        $attributeMetadata = $this->getMockBuilder(AttributeMetadata::class)->disableOriginalConstructor()->getMock();
        $attributeMetadata->method('getOptions')->willReturn([$option]);
        $attributeMetadata->method('isVisible')->willReturn(true);

        $this->customerMetaData->method('getAttributeMetadata')->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__("Error")));

        $this->checkoutHelper->method('getCurrentCheckoutMethod')->willReturn('guest');
        $this->checkoutHelper->method('getConfigParam')->willReturn('CE');

        $subject = $this->getMockBuilder(\Magento\Checkout\Block\Checkout\LayoutProcessor::class)->disableOriginalConstructor()->getMock();

        $inputArray = ['key' => 'value'];

        $result = $this->classToTest->afterProcess($subject, $inputArray);
        $this->assertCount(2, $result);
    }

    public function testAfterProcessNoGuestCheckout()
    {
        $this->checkoutHelper->method('getCurrentCheckoutMethod')->willReturn('register');

        $subject = $this->getMockBuilder(\Magento\Checkout\Block\Checkout\LayoutProcessor::class)->disableOriginalConstructor()->getMock();

        $inputArray = ['key' => 'value'];

        $result = $this->classToTest->afterProcess($subject, $inputArray);
        $this->assertCount(1, $result);
    }

    public function testAfterProcessCreditratingDisabled()
    {
        $this->checkoutHelper->method('getCurrentCheckoutMethod')->willReturn('guest');
        $this->checkoutHelper->method('getConfigParam')->willReturn(null);

        $subject = $this->getMockBuilder(\Magento\Checkout\Block\Checkout\LayoutProcessor::class)->disableOriginalConstructor()->getMock();

        $inputArray = ['key' => 'value'];

        $result = $this->classToTest->afterProcess($subject, $inputArray);
        $this->assertCount(1, $result);
    }

    public function testAfterProcessCreditratingNoBoniversum()
    {
        $this->checkoutHelper->method('getCurrentCheckoutMethod')->willReturn('guest');
        $this->checkoutHelper->method('getConfigParam')->willReturnMap(
            [
                ['enabled', 'creditrating', 'payone_protect', null, true],
                ['type', 'creditrating', 'payone_protect', null, 'not boniversum']
            ]
        );

        $subject = $this->getMockBuilder(\Magento\Checkout\Block\Checkout\LayoutProcessor::class)->disableOriginalConstructor()->getMock();

        $inputArray = ['key' => 'value'];

        $result = $this->classToTest->afterProcess($subject, $inputArray);
        $this->assertCount(1, $result);
    }
}
