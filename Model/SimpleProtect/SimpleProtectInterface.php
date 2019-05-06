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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\SimpleProtect;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\AddressInterface;

interface SimpleProtectInterface
{
    /**
     * This method can be extended for individual custom behaviour
     *
     * Extending this method gives the following possibilities:
     * 1. Filtering out payment methods based on your own rule set
     * 2. Throwing a LocalizedException to send the user back to shipping method selection
     *
     * @param  Quote             $oQuote
     * @param  MethodInterface[] $aPaymentMethods
     * @return MethodInterface[]
     */
    public function handlePrePaymentSelection(Quote $oQuote, $aPaymentMethods);

    /**
     * This method can be extended for individual custom behaviour
     *
     * Extending this method gives the following possibilities:
     * 1. Throwing a LocalizedException will stop the order creation and throw the user back to payment selection with the given thrown message
     * 2. Finishing the method - so throwing no Exception will finish the order creation
     *
     * @param  Quote $oQuote
     * @return void
     * @throws LocalizedException
     */
    public function handlePostPaymentSelection(Quote $oQuote);

    /**
     * This method can be extended for individual custom behaviour
     *
     * Extending this method gives the following possibilities:
     * 1. Returning true will just continue the process without changing anything
     * 2. Returning a (changed) address object instance of AddressInterface will show an address correction prompt to the customer
     * 3. Throwing a LocalizedException will show the given exception message to the customer
     *
     * @param AddressInterface $oAddressData
     * @param bool             $blIsVirtual
     * @param double           $dTotal
     * @return AddressInterface|bool
     * @throws LocalizedException
     */
    public function handleEnterOrChangeBillingAddress(AddressInterface $oAddressData, $blIsVirtual, $dTotal);

    /**
     * This method can be extended for individual custom behaviour
     *
     * Extending this method gives the following possibilities:
     * 1. Returning true will just continue the process without changing anything
     * 2. Returning a (changed) address object instance of AddressInterface will show an address correction prompt to the customer
     * 3. Throwing a LocalizedException will show the given exception message to the customer
     *
     * @param AddressInterface $oAddressData
     * @param bool             $blIsVirtual
     * @param double           $dTotal
     * @return AddressInterface|bool
     * @throws LocalizedException
     */
    public function handleEnterOrChangeShippingAddress(AddressInterface $oAddressData, $blIsVirtual, $dTotal);

    /**
     * This method can be extended for individual custom behaviour
     *
     * Extend this method to return true to enable addresscheck frontend ajax calls for billing address
     *
     * @return bool
     */
    public function isAddresscheckBillingEnabled();

    /**
     * This method can be extended for individual custom behaviour
     *
     * Extend this method to return true to enable addresscheck frontend ajax calls for shipping address
     *
     * @return bool
     */
    public function isAddresscheckShippingEnabled();

    /**
     * This method can be extended for individual custom behaviour
     *
     * Extend this method to return false to disable address correction confirmation
     *
     * @return bool
     */
    public function isAddresscheckCorrectionConfirmationNeeded();
}
