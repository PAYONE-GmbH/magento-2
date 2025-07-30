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

use Magento\Quote\Model\Quote\Address;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order as SalesOrder;

/**
 * Helper class for everything that has to do with orders
 */
class Order extends \Payone\Core\Helper\Base
{
    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * PAYONE customer helper
     *
     * @var \Payone\Core\Helper\Customer
     */
    protected $customerHelper;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * Totals collector object
     *
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Framework\App\State               $state
     * @param \Payone\Core\Helper\Database               $databaseHelper
     * @param \Payone\Core\Helper\Customer               $customerHelper
     * @param \Magento\Sales\Model\OrderFactory          $orderFactory
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\State $state,
        \Payone\Core\Helper\Database $databaseHelper,
        \Payone\Core\Helper\Customer $customerHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
    ) {
        parent::__construct($context, $storeManager, $shopHelper, $state);
        $this->databaseHelper = $databaseHelper;
        $this->customerHelper = $customerHelper;
        $this->orderFactory = $orderFactory;
        $this->totalsCollector = $totalsCollector;
    }

    /**
     * Return the order related to the given TransactionStatus
     *
     * @param  string $sTxid
     * @return SalesOrder|null
     */
    public function getOrderByTxid($sTxid)
    {
        $sIncrementId = $this->databaseHelper->getOrderIncrementIdByTxid($sTxid);
        $oOrder = $this->orderFactory->create()->loadByIncrementId($sIncrementId);
        if ($oOrder && $oOrder->getId()) {
            return $oOrder;
        }
        return null;
    }

    /**
     * Return the order related to the given order id
     *
     * @param  string $sOrderId
     * @return SalesOrder|null
     */
    public function getOrderById($sOrderId)
    {
        $oOrder = $this->orderFactory->create()->load($sOrderId);
        if ($oOrder && $oOrder->getId()) {
            return $oOrder;
        }
        return null;
    }

    /**
     * Fill billing and shipping addresses with the needed information from the response
     *
     * @param  Address $oAddress
     * @param  string  $sFirstname
     * @param  string  $sLastname
     * @param  string  $sStreet
     * @param  string  $sCity
     * @param  string  $sZip
     * @param  string  $sCountry
     * @param  string  $sState
     * @return Address
     */
    public function fillSingleAddress(Address $oAddress, $sFirstname, $sLastname, $sStreet, $sCity, $sZip, $sCountry, $sState)
    {
        $oAddress->setFirstname($sFirstname);
        $oAddress->setLastname($sLastname);
        $oAddress->setStreet($sStreet);
        $oAddress->setCity($sCity);
        $oAddress->setPostcode($sZip);
        $oAddress->setCountryId($sCountry);

        $oRegion = $this->customerHelper->getRegion($sCountry, $sState);
        if ($oRegion) {
            $oAddress->setRegionId($oRegion->getId());
            $oAddress->setRegionCode($sState);
        } else {
            $oAddress->setRegionId(null);
            $oAddress->setRegionCode(null);
        }
        return $oAddress;
    }

    /**
     * Map response parameters to distinct parameters
     *
     * @param  Address $oAddress
     * @param  array   $aResponse
     * @param  bool    $blIsBilling
     * @return Address
     */
    public function fillSingleAddressByResponse($oAddress, $aResponse, $blIsBilling = false)
    {
        $sPrefix = 'shipping';
        if ($blIsBilling === true) {
            $sPrefix = 'billing';
        }
        return $this->fillSingleAddress(
            $oAddress,
            $aResponse['add_paydata['.$sPrefix.'_firstname]'],
            $aResponse['add_paydata['.$sPrefix.'_lastname]'],
            $aResponse['add_paydata['.$sPrefix.'_street]'],
            $aResponse['add_paydata['.$sPrefix.'_city]'],
            $aResponse['add_paydata['.$sPrefix.'_zip]'],
            $aResponse['add_paydata['.$sPrefix.'_country]'],
            $aResponse['add_paydata['.$sPrefix.'_country]']
        );
    }

    /**
     * Address-handling for billing- and shipping address
     *
     * @param  Quote $oQuote
     * @param  array $aResponse
     * @param  bool $blUseBilling
     * @return Quote
     */
    public function updateAddresses(Quote $oQuote, $aResponse, $blUseBilling = false)
    {
        $oBillingAddress = $this->fillSingleAddressByResponse($oQuote->getBillingAddress(), $aResponse, $blUseBilling);
        $oBillingAddress->setEmail($aResponse['add_paydata[email]']);

        $oQuote->setBillingAddress($oBillingAddress);
        $oQuote->getBillingAddress()->setShouldIgnoreValidation(true);

        if (!$oQuote->getIsVirtual()) {
            $oShippingAddress = $this->fillSingleAddressByResponse($oQuote->getShippingAddress(), $aResponse);
            #$oShippingAddress->setCollectShippingRates(true);
            $this->setShippingMethod($oShippingAddress, $oQuote);
            $oQuote->setShippingAddress($oShippingAddress);
            $oQuote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        return $oQuote;
    }

    /**
     * Determine the cheapest available shipping method
     *
     * @param  Quote   $oQuote
     * @param  Address $oShippingAddress
     * @return string|bool
     */
    public function getShippingMethod(Quote $oQuote, Address $oShippingAddress)
    {
        $aRates = [];

        // Needed for getGroupedAllShippingRates, otherwise sometimes empty output
        $this->totalsCollector->collectAddressTotals($oQuote, $oShippingAddress);
        $oShippingRates = $oShippingAddress->getGroupedAllShippingRates();

        foreach ($oShippingRates as $oCarrierRates) {
            foreach ($oCarrierRates as $oRate) {
                $aRates[(string)$oRate->getPrice()] = $oRate->getCode();
            }
        }

        if (!empty($aRates)) { // more than one shipping method existing?
            ksort($aRates); // sort by price ascending
            return array_shift($aRates); // return the cheapest shipping-method
        }
        return false;
    }

    /**
     * Get Shipping method and add it to the shipping-address object
     *
     * @param  Address $oAddress
     * @param  Quote   $oQuote
     * @return Address
     * @throws LocalizedException
     */
    public function setShippingMethod(Address $oAddress, Quote $oQuote)
    {
        $oAddress->setCollectShippingRates(true);

        $sShippingMethod = $this->getShippingMethod($oQuote, $oAddress);
        if (!$sShippingMethod) {
            throw new LocalizedException(__("No shipping method available for your address!"));
        }
        $oAddress->setShippingMethod($sShippingMethod);

        return $oAddress;
    }
}
