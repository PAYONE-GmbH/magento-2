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

namespace Payone\Core\Block;

use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Session;

/**
 * Block class for re-setting the guest checkout information
 */
class RedirectReturn extends Template
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Session          $checkoutSession
     * @param array            $data
     */
    public function __construct(Template\Context $context, Session $checkoutSession, array $data = [])
    {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Determine if cart needs to be invalidated/refreshed
     *
     * @return bool
     */
    public function isRedirectCancellation() {
        if ($this->checkoutSession->getIsPayoneRedirectCancellation()) {
            $this->checkoutSession->unsIsPayoneRedirectCancellation();
            return true;
        }
        return false;
    }

    /**
     * Returns quote object from session
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get shipping address
     *
     * @return Quote\Address|false
     */
    public function getShippingAddress()
    {
        $oQuote = $this->getQuote();
        if ($oQuote) {
            return $oQuote->getShippingAddress();
        }
        return false;
    }

    /**
     * Get quote payment object
     *
     * @return Quote\Payment|false
     */
    public function getQuotePayment()
    {
        $oQuote = $this->getQuote();
        if ($oQuote) {
            return $oQuote->getPayment();
        }
        return false;
    }

    /**
     * Return if the customer is a guest
     *
     * @return bool|mixed|null
     */
    public function isGuest()
    {
        return $this->getQuote()->getCustomerIsGuest();
    }
}
