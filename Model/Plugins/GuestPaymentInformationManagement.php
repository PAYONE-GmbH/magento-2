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

namespace Payone\Core\Model\Plugins;

use Magento\Checkout\Model\GuestPaymentInformationManagement as GuestPaymentInformationManagementOrig;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Plugin for Magentos GuestPaymentInformationManagement class
 */
class GuestPaymentInformationManagement
{
    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Guest cart management object
     *
     * @var \Magento\Quote\Api\GuestCartManagementInterface
     */
    protected $cartManagement;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                        $shopHelper
     * @param \Magento\Quote\Api\GuestCartManagementInterface $cartManagement
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Quote\Api\GuestCartManagementInterface $cartManagement
    ) {
        $this->shopHelper = $shopHelper;
        $this->cartManagement = $cartManagement;
    }

    /**
     * Fixes problem in Magento 2.1 that the error message isnt sent through to the frontend
     *
     * @param  GuestPaymentInformationManagementOrig         $subject
     * @param  callable                                      $proceed
     * @param  int                                           $cartId
     * @param  string                                        $email
     * @param  \Magento\Quote\Api\Data\PaymentInterface      $paymentMethod
     * @param  \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws CouldNotSaveException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementOrig $subject,
        callable $proceed,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if (version_compare($this->shopHelper->getMagentoVersion(), '2.1.0', '>=') &&
            version_compare($this->shopHelper->getMagentoVersion(), '2.2.0', '<') &&
            strpos($paymentMethod->getMethod(), 'payone_') !== false
        ) { // is Magento 2.1.X
            $subject->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
            try {
                $orderId = $this->cartManagement->placeOrder($cartId);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                throw new CouldNotSaveException(__($e->getMessage()), $e);
            } catch (\Exception $e) {
                throw new CouldNotSaveException(__('An error occurred on the server. Please try to place the order again.'), $e);
            }
            return $orderId;
        }
        // execute standard functionality
        return $proceed($cartId, $email, $paymentMethod, $billingAddress);
    }
}
