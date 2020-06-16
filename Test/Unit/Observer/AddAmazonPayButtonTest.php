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

namespace Payone\Core\Test\Unit\Observer;

use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\Paypal\Block\Express\Shortcut;
use Payone\Core\Helper\Payment;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Observer\AddAmazonPayButton as ClassToTest;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Payone\Core\Model\SimpleProtect\SimpleProtect;

class AddAmazonPayButtonTest extends BaseTestCase
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
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var SimpleProtect|\PHPUnit_Framework_MockObject_MockObject
     */
    private $simpleProtect;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCurrentlySecure'])
            ->getMock();
        $store->method('isCurrentlySecure')->willReturn(true);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)->disableOriginalConstructor()->getMock();
        $this->simpleProtect = $this->getMockBuilder(SimpleProtect::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'paymentHelper' => $this->paymentHelper,
            'storeManager' => $storeManager,
            'checkoutSession' => $this->checkoutSession,
            'simpleProtect' => $this->simpleProtect
        ]);
    }

    public function testExecuteInactive()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(false);
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();

        $this->classToTest->execute($observer);
        $this->assertTrue(true);
    }

    public function testExecuteActive()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(true);

        $shortcut = $this->getMockBuilder(Shortcut::class)->disableOriginalConstructor()->getMock();

        $layout = $this->getMockBuilder(LayoutInterface::class)->disableOriginalConstructor()->getMock();
        $layout->method('createBlock')->willReturn($shortcut);

        $shortcutButtons = $this->getMockBuilder(ShortcutButtons::class)->disableOriginalConstructor()->getMock();
        $shortcutButtons->method('getLayout')->willReturn($layout);

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer'])
            ->getMock();
        $event->method('getContainer')->willReturn($shortcutButtons);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->simpleProtect->method('handlePreCheckout')->willReturn([PayoneConfig::EXPRESS_AMAZONPAY]);

        $executed = false;

        /** @var Observer $observer */
        $this->classToTest->execute($observer);
        $executed = true;
        $this->assertTrue($executed);
    }

    public function testExecutePaypalActiveAddToCart()
    {
        $this->paymentHelper->method('isPayPalExpressActive')->willReturn(true);

        $shortcut = $this->getMockBuilder(Shortcut::class)->disableOriginalConstructor()->getMock();

        $layout = $this->getMockBuilder(LayoutInterface::class)->disableOriginalConstructor()->getMock();
        $layout->method('createBlock')->willReturn($shortcut);

        $shortcutButtons = $this->getMockBuilder(ShortcutButtons::class)->disableOriginalConstructor()->getMock();
        $shortcutButtons->method('getNameInLayout')->willReturn('addtocart.shortcut.buttons');
        $shortcutButtons->method('getLayout')->willReturn($layout);

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer'])
            ->getMock();
        $event->method('getContainer')->willReturn($shortcutButtons);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $executed = false;

        /** @var Observer $observer */
        $this->classToTest->execute($observer);
        $executed = true;
        $this->assertTrue($executed);
    }

    public function testExecuteSimpleProtectDeactivated()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(true);

        $shortcut = $this->getMockBuilder(Shortcut::class)->disableOriginalConstructor()->getMock();

        $layout = $this->getMockBuilder(LayoutInterface::class)->disableOriginalConstructor()->getMock();
        $layout->method('createBlock')->willReturn($shortcut);

        $shortcutButtons = $this->getMockBuilder(ShortcutButtons::class)->disableOriginalConstructor()->getMock();
        $shortcutButtons->method('getLayout')->willReturn($layout);

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer'])
            ->getMock();
        $event->method('getContainer')->willReturn($shortcutButtons);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->simpleProtect->method('handlePreCheckout')->willReturn([PayoneConfig::EXPRESS_PAYPAL]);

        $executed = false;

        /** @var Observer $observer */
        $this->classToTest->execute($observer);
        $executed = true;
        $this->assertTrue($executed);
    }
}
