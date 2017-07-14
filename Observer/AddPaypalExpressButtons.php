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
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Payone\Core\Helper\Payment;
use Magento\Framework\Event\Observer;
use Magento\Paypal\Block\Express\Shortcut;

/**
 * Event class to add the PayPal Express buttons to the frontend
 */
class AddPaypalExpressButtons implements ObserverInterface
{
    /**
     * PAYONE payment helper
     *
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * Constructor
     *
     * @param Payment $paymentHelper
     */
    public function __construct(Payment $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Add PayPal shortcut buttons
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->paymentHelper->isPayPalExpressActive() === false) {
            return;
        }

        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        if ($shortcutButtons->getNameInLayout() === 'addtocart.shortcut.buttons') {
            return;
        }

        /** @var Shortcut $shortcut */
        $shortcut = $shortcutButtons->getLayout()->createBlock(
            'Payone\Core\Block\Paypal\ExpressButton',
            '',
            []
        );

        $shortcutButtons->addShortcut($shortcut);
    }
}
