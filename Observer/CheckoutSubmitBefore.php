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
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Model\Source\CreditratingIntegrationEvent as Event;
use Payone\Core\Model\Source\PersonStatus;

/**
 * Event class to set the orderstatus to new and pending
 */
class CheckoutSubmitBefore implements ObserverInterface
{
    /**
     * PAYONE Simple Protect implementation
     *
     * @var \Payone\Core\Model\SimpleProtect\SimpleProtectInterface
     */
    protected $simpleProtect;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\SimpleProtect\SimpleProtectInterface $simpleProtect
     */
    public function __construct(
        \Payone\Core\Model\SimpleProtect\SimpleProtectInterface $simpleProtect
    ) {
        $this->simpleProtect = $simpleProtect;
    }

    /**
     * Execute certain tasks after the payment is placed and thus the order is placed
     *
     * @param  Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $oQuote */
        $oQuote = $observer->getQuote();
        if (!$oQuote) {
            return;
        }

        // Send code execution to simple protect custom implementation
        // Only possible interaction in there is throwing an exception to stop order creation
        $this->simpleProtect->handlePostPaymentSelection($oQuote);
    }
}
