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

namespace Payone\Core\Model\Paypal;

use Payone\Core\Model\PayoneConfig;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

/**
 * Handles the quote object after the return from PayPal
 */
class ReturnHandler
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE genericpayment object
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress
     */
    protected $genericRequest;

    /**
     * PayPal payment model
     *
     * @var \Payone\Core\Model\Methods\Paypal
     */
    protected $paypalPayment;

    /**
     * Cart management interface
     *
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Order
     */
    protected $orderHelper;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session                             $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress $genericRequest
     * @param \Payone\Core\Model\Methods\Paypal                           $paypalPayment
     * @param \Magento\Quote\Api\CartManagementInterface                  $cartManagement
     * @param \Payone\Core\Helper\Order                                   $orderHelper
     * @param \Payone\Core\Helper\Checkout                                $checkoutHelper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress $genericRequest,
        \Payone\Core\Model\Methods\Paypal $paypalPayment,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Payone\Core\Helper\Order $orderHelper,
        \Payone\Core\Helper\Checkout $checkoutHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->genericRequest = $genericRequest;
        $this->paypalPayment = $paypalPayment;
        $this->cartManagement = $cartManagement;
        $this->orderHelper = $orderHelper;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * Map response parameters to distinct parameters
     *
     * @param  Address $oAddress
     * @param  array   $aResponse
     * @return Address
     */
    protected function fillSingleAddress($oAddress, $aResponse)
    {
        return $this->orderHelper->fillSingleAddress(
            $oAddress,
            $aResponse['add_paydata[shipping_firstname]'],
            $aResponse['add_paydata[shipping_lastname]'],
            $aResponse['add_paydata[shipping_street]'],
            $aResponse['add_paydata[shipping_city]'],
            $aResponse['add_paydata[shipping_zip]'],
            $aResponse['add_paydata[shipping_country]'],
            $aResponse['add_paydata[shipping_country]']
        );
    }

    /**
     * Address-handling for billing- and shipping address
     *
     * @param  Quote $oQuote
     * @param  array $aResponse
     * @return Quote
     */
    protected function handleAddresses(Quote $oQuote, $aResponse)
    {
        $oBillingAddress = $this->fillSingleAddress($oQuote->getBillingAddress(), $aResponse);
        $oBillingAddress->setEmail($aResponse['add_paydata[email]']);

        $oQuote->setBillingAddress($oBillingAddress);
        $oQuote->getBillingAddress()->setShouldIgnoreValidation(true);

        if (!$oQuote->getIsVirtual()) {
            $oShippingAddress = $this->fillSingleAddress($oQuote->getShippingAddress(), $aResponse);
            $oShippingAddress = $this->orderHelper->setShippingMethod($oShippingAddress, $oQuote);
            $oQuote->setShippingAddress($oShippingAddress);
            $oQuote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        return $oQuote;
    }

    /**
     * Add all necessary information to the quote
     *
     * @param  Quote $oQuote
     * @param  array $aResponse
     * @return Quote
     */
    protected function handleQuote(Quote $oQuote, $aResponse)
    {
        $oQuote = $this->handleAddresses($oQuote, $aResponse);

        if ($this->checkoutHelper->getCurrentCheckoutMethod($oQuote) == Onepage::METHOD_GUEST) {
            $oQuote->setCustomerId(null)
                ->setCustomerEmail($oQuote->getBillingAddress()->getEmail())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        }

        $oPayment = $oQuote->getPayment();
        $oPayment->setMethod(PayoneConfig::METHOD_PAYPAL);

        $oQuote->setPayment($oPayment);
        $oQuote->setInventoryProcessed(false);
        $oQuote->collectTotals()->save();

        return $oQuote;
    }

    /**
     * Quote handling for PayPal return
     *
     * @param  string $sWorkorderId
     * @return void
     */
    public function handlePayPalReturn($sWorkorderId)
    {
        $oQuote = $this->checkoutSession->getQuote();
        $aResponse = $this->genericRequest->sendRequest($oQuote, $this->paypalPayment, $sWorkorderId);

        $oQuote = $this->handleQuote($oQuote, $aResponse);
        #$this->cartManagement->placeOrder($oQuote->getId());
        #$oQuote->setIsActive(false)->save();
    }
}
