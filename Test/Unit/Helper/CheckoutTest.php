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
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Session;
use Magento\Checkout\Helper\Data;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

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

    protected function setUp()
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
}
