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

namespace Payone\Core\Observer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ObserverInterface;
use Payone\Core\Helper\Payment;
use Magento\Framework\Event\Observer;
use Magento\Paypal\Block\Express\Shortcut;
use Magento\Store\Model\StoreManagerInterface;
use Payone\Core\Model\PayoneConfig;

/**
 * Event class to add the Paydirekt Oneklick buttons to the frontend
 */
class AddPaydirektOneklickButton implements ObserverInterface
{
    /**
     * PAYONE payment helper
     *
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * Store manager object
     *
     * @var StoreManagerInterface\
     */
    protected $storeManager;

    /**
     * Customer session object
     *
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * Constructor.
     *
     * @param Payment               $paymentHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession       $customerSession
     */
    public function __construct(Payment $paymentHelper, StoreManagerInterface $storeManager, CustomerSession $customerSession)
    {
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Add PayPal shortcut buttons
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->paymentHelper->isPaymentMethodActive(PayoneConfig::METHOD_PAYDIREKT) === false) {
            return;
        }

        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        if (in_array($shortcutButtons->getNameInLayout(), ['addtocart.shortcut.buttons', 'addtocart.shortcut.buttons.additional', 'map.shortcut.buttons'])) {
            return;
        }

        if ($this->customerSession->isLoggedIn() === false || (bool)$this->customerSession->getCustomer()->getPayonePaydirektRegistered() === false) {
            return;
        }

        /** @var Shortcut $shortcut */
        $shortcut = $shortcutButtons->getLayout()->createBlock(
            'Payone\Core\Block\Paydirekt\OneklickButton',
            '',
            []
        );

        $shortcutButtons->addShortcut($shortcut);
    }
}
