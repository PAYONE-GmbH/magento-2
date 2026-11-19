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

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\Checkout;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Customer\Model\Session;
use Magento\Checkout\Helper\Data;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

#[AllowMockObjectsWithoutExpectations]
class CheckoutTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Checkout
     */
    private $checkout;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutData;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->checkoutData = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();

        $this->checkout = $this->objectManager->getObject(Checkout::class, [
            'customerSession' => $this->customerSession,
            'checkoutData' => $this->checkoutData
        ]);

        $this->quote = $this->objectManager->getObject(Quote::class);
    }

    public function testCurrentCheckoutMethodLoggedIn()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $result = $this->checkout->getCurrentCheckoutMethod($this->quote);
        $expected = Onepage::METHOD_CUSTOMER;
        $this->assertEquals($expected, $result);
    }

    public function testCurrentCheckoutMethodPreset()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);

        $expected = 'random value';
        $this->quote->setCheckoutMethod($expected);
        $result = $this->checkout->getCurrentCheckoutMethod($this->quote);
        $this->assertEquals($expected, $result);
    }

    public function testCurrentCheckoutMethodGuest()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->checkoutData->method('isAllowedGuestCheckout')->willReturn(true);

        $this->quote->setCheckoutMethod(false);
        $result = $this->checkout->getCurrentCheckoutMethod($this->quote);
        $expected = Onepage::METHOD_GUEST;
        $this->assertEquals($expected, $result);
    }

    public function testCurrentCheckoutMethodRegister()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->checkoutData->method('isAllowedGuestCheckout')->willReturn(false);

        $this->quote->setCheckoutMethod(false);
        $result = $this->checkout->getCurrentCheckoutMethod($this->quote);
        $expected = Onepage::METHOD_REGISTER;
        $this->assertEquals($expected, $result);
    }

    public function testGetQuoteComparisonString()
    {
        $productId = "4711";
        $sku = "Test12";
        $qty = 5;

        $expected = $productId.$sku.$qty."|";

        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getSku',
                'getQty',
            ])
            ->getMock();
        $item->setData('product_id', $productId);
        $item->method("getSku")->willReturn("Test12");
        $item->method("getQty")->willReturn(5);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getAllItems')->willReturn([$item]);

        $result = $this->checkout->getQuoteComparisonString($quote);

        $this->assertEquals($expected, $result);
    }

    public function testGetHashFromAddressAllStreetLines()
    {
        $address1 = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet', 'getRegionCode'])->getMock();
        $address1->method('getStreet')->willReturn(['Teststr.', '12']);
        $address1->setData(['firstname' => 'Paul', 'lastname' => 'Payer', 'postcode' => '12345', 'city' => 'Berlin', 'country_id' => 'DE']);

        $address2 = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet', 'getRegionCode'])->getMock();
        $address2->method('getStreet')->willReturn(['Teststr.', '13']);
        $address2->setData(['firstname' => 'Paul', 'lastname' => 'Payer', 'postcode' => '12345', 'city' => 'Berlin', 'country_id' => 'DE']);

        $this->assertNotEquals($this->checkout->getHashFromAddress($address1), $this->checkout->getHashFromAddress($address2));
    }

    public function testGetHashFromAddressCorrectedStreet()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet', 'getRegionCode'])->getMock();
        $address->method('getStreet')->willReturn(['Main 1', 'Apt 2']);
        $address->setData(['firstname' => 'Paul', 'lastname' => 'Payer', 'postcode' => '12345', 'city' => 'Berlin', 'country_id' => 'DE']);

        // the checkout replaces only the first street line with the corrected street
        $correctedAddress = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet', 'getRegionCode'])->getMock();
        $correctedAddress->method('getStreet')->willReturn(['Mainstr. 1', 'Apt 2']);
        $correctedAddress->setData(['firstname' => 'Paul', 'lastname' => 'Payer', 'postcode' => '12345', 'city' => 'Berlin', 'country_id' => 'DE']);

        $this->assertEquals(
            $this->checkout->getHashFromAddress($correctedAddress),
            $this->checkout->getHashFromAddress($address, ['street' => 'Mainstr. 1'])
        );
    }
}
