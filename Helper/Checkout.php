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
use Magento\Quote\Api\Data\AddressInterface;

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
     * All parameters used for the address hash
     *
     * @var array
     */
    protected $aHashParams = [
        'firstname',
        'lastname',
        'company',
        'street',
        'zip',
        'city',
        'country',
        'state',
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Customer\Model\Session            $customerSession
     * @param \Magento\Checkout\Helper\Data              $checkoutData
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\State $state,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $checkoutData
    ) {
        parent::__construct($context, $storeManager, $shopHelper, $state);
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

    /**
     * Generate a string that represents the basket, used to compare it at different times during checkout
     *
     * @param  Quote $oQuote
     * @return string
     */
    public function getQuoteComparisonString(Quote $oQuote)
    {
        $sComparisonString = "";
        foreach ($oQuote->getAllItems() as $oItem) { // add invoice items for all order items
            $sComparisonString .= $oItem->getProductId().$oItem->getSku().$oItem->getQty()."|";
        }
        return $sComparisonString;
    }

    /**
     * Get address array for hash creation
     *
     * @param  AddressInterface $oAddress
     * @return array
     */
    protected function getAddressArray(AddressInterface $oAddress)
    {
        return [
            'firstname' => $oAddress->getFirstname(),
            'lastname' => $oAddress->getLastname(),
            'company' => $oAddress->getCompany(),
            'street' => $oAddress->getStreet()[0],
            'zip' => $oAddress->getPostcode(),
            'city' => $oAddress->getCity(),
            'country' => $oAddress->getCountryId(),
            'state' => $oAddress->getRegionCode(),
        ];
    }

    /**
     * Generate a unique hash of an address
     *
     * @param  AddressInterface $oAddress
     * @param  array            $aResponse
     * @return string
     */
    public function getHashFromAddress(AddressInterface $oAddress, $aResponse = false)
    {
        $aAddressArray = $this->getAddressArray($oAddress); // collect data from the address object

        $sAddress = '';
        foreach ($this->aHashParams as $sParamKey) {
            $sParamValue = isset($aAddressArray[$sParamKey]) ? $aAddressArray[$sParamKey] : false;
            if ($sParamValue) {
                if ($aResponse !== false && array_key_exists($sParamKey, $aResponse) !== false && $aResponse[$sParamKey] != $sParamValue) {
                    //take the corrected value from the address-check
                    $sParamValue = $aResponse[$sParamKey];
                }
                $sAddress .= $sParamValue;
            }
        }
        $sHash = md5($sAddress); // generate hash from address for identification

        return $sHash;
    }

    /**
     * Generate hash for the addresses used in the quote at the current moment
     *
     * @param Quote $oQuote
     * @return string
     */
    public function getQuoteAddressHash(Quote $oQuote)
    {
        $sHash = $this->getHashFromAddress($oQuote->getBillingAddress());
        if ($oQuote->getIsVirtual() === false && !empty($oQuote->getShippingAddress())) {
            $sHash .= $this->getHashFromAddress($oQuote->getShippingAddress());
        }
        return $sHash;
    }
}
