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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Model\Plugins;

use Payone\Core\Model\Plugins\GuestPaymentInformationManagement as ClassToTest;
use Magento\Checkout\Model\GuestPaymentInformationManagement as GuestPaymentInformationManagementOrig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Payone\Core\Helper\Shop;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Framework\Exception\LocalizedException;

class GuestPaymentInformationManagementTest extends BaseTestCase
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
     * @var Shop
     */
    private $shopHelper;

    /**
     * @var GuestCartManagementInterface
     */
    private $cartManagement;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $this->cartManagement = $this->getMockBuilder(GuestCartManagementInterface::class)->disableOriginalConstructor()->getMock();


        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'shopHelper' => $this->shopHelper,
            'cartManagement' => $this->cartManagement
        ]);
    }

    public function testAroundSavePaymentInformationAndPlaceOrderParentPre()
    {
        $this->shopHelper->method('getMagentoVersion')->willReturn('2.0.11');

        $cartId = '12345';

        $subject = $this->getMockBuilder(GuestPaymentInformationManagementOrig::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($cartId) {
            return $cartId;
        };
        $email = 'tester@test.com';
        $paymentMethod = $this->getMockBuilder(PaymentInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->aroundSavePaymentInformationAndPlaceOrder($subject, $proceed, $cartId, $email, $paymentMethod, null);
        $this->assertEquals($cartId, $result);
    }

    public function testAroundSavePaymentInformationAndPlaceOrderParentPost()
    {
        $this->shopHelper->method('getMagentoVersion')->willReturn('2.2.0');

        $cartId = '12345';

        $subject = $this->getMockBuilder(GuestPaymentInformationManagementOrig::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($cartId) {
            return $cartId;
        };
        $email = 'tester@test.com';
        $paymentMethod = $this->getMockBuilder(PaymentInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->aroundSavePaymentInformationAndPlaceOrder($subject, $proceed, $cartId, $email, $paymentMethod, null);
        $this->assertEquals($cartId, $result);
    }

    public function testAroundSavePaymentInformationAndPlaceOrderSuccess()
    {
        $cartId = '12345';
        $orderId = '54321';

        $this->shopHelper->method('getMagentoVersion')->willReturn('2.1.0');
        $this->cartManagement->method('placeOrder')->willReturn($orderId);

        $subject = $this->getMockBuilder(GuestPaymentInformationManagementOrig::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($cartId) {
            return $cartId;
        };
        $email = 'tester@test.com';
        $paymentMethod = $this->getMockBuilder(PaymentInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getMethod')->willReturn('payone_safe_invoice');

        $result = $this->classToTest->aroundSavePaymentInformationAndPlaceOrder($subject, $proceed, $cartId, $email, $paymentMethod, null);
        $this->assertEquals($orderId, $result);
    }

    public function testAroundSavePaymentInformationAndPlaceOrderException()
    {
        $cartId = '12345';

        $exception = new \Exception();
        $this->shopHelper->method('getMagentoVersion')->willReturn('2.1.0');
        $this->cartManagement->method('placeOrder')->willThrowException($exception);

        $subject = $this->getMockBuilder(GuestPaymentInformationManagementOrig::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($cartId) {
            return $cartId;
        };
        $email = 'tester@test.com';
        $paymentMethod = $this->getMockBuilder(PaymentInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getMethod')->willReturn('payone_safe_invoice');

        $this->expectException(CouldNotSaveException::class);
        $this->classToTest->aroundSavePaymentInformationAndPlaceOrder($subject, $proceed, $cartId, $email, $paymentMethod, null);
    }

    public function testAroundSavePaymentInformationAndPlaceOrderLocalized()
    {
        $cartId = '12345';

        $exception = new LocalizedException(__('Localized message'));
        $this->shopHelper->method('getMagentoVersion')->willReturn('2.1.0');
        $this->cartManagement->method('placeOrder')->willThrowException($exception);

        $subject = $this->getMockBuilder(GuestPaymentInformationManagementOrig::class)->disableOriginalConstructor()->getMock();
        $proceed = function () use ($cartId) {
            return $cartId;
        };
        $email = 'tester@test.com';
        $paymentMethod = $this->getMockBuilder(PaymentInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getMethod')->willReturn('payone_safe_invoice');

        $this->expectException(CouldNotSaveException::class);
        $this->classToTest->aroundSavePaymentInformationAndPlaceOrder($subject, $proceed, $cartId, $email, $paymentMethod, null);
    }
}
