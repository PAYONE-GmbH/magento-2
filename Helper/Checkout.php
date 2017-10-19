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

namespace Payone\Core\Helper;

use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;

/**
 * Helper class for everything that has to do with the checkout
 */
class Checkout extends \Payone\Core\Helper\Base
{
    /**
     * Checkout session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutData;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Customer\Model\Session            $customerSession
     * @param \Magento\Checkout\Helper\Data              $checkoutData
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $checkoutData
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->customerSession = $customerSession;
        $this->checkoutData = $checkoutData;
    }

    /**
     * Get checkout method
     *
     * @param  Quote $oQuote
     * @return string
     */
    public function getCurrentCheckoutMethod(Quote $oQuote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$oQuote->getCheckoutMethod()) {
            if ($this->checkoutData->isAllowedGuestCheckout($oQuote)) {
                $oQuote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $oQuote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }
        return $oQuote->getCheckoutMethod();
    }
}
