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

use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Directory\Model\Region;

/**
 * Helper class for everything that has to do with customers
 */
class Customer extends \Payone\Core\Helper\Base
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Region factory
     *
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Checkout\Model\Session            $checkoutSession
     * @param \Magento\Directory\Model\RegionFactory     $regionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\RegionFactory $regionFactory
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->checkoutSession = $checkoutSession;
        $this->regionFactory = $regionFactory;
    }

    /**
     * Determine if the customer entered his gender
     *
     * @return bool
     */
    public function customerHasGivenGender()
    {
        $oCustomer = $this->checkoutSession->getQuote()->getCustomer();
        if ($oCustomer && $oCustomer->getGender()) {
            return true;
        }
        return false;
    }

    /**
     * Determine if the customer entered his birthday
     *
     * @return string|bool
     */
    public function getCustomerBirthday()
    {
        $oCustomer = $this->checkoutSession->getQuote()->getCustomer();
        if ($oCustomer && $oCustomer->getDob()) {
            return $oCustomer->getDob();
        }
        return false;
    }

    /**
     * Get the region object for the state and country given by PayPal
     *
     * @param  string $sCountry
     * @param  string $sState
     * @return Region|bool
     */
    public function getRegion($sCountry, $sState)
    {
        $oRegion = false;
        if (!empty($sState) && $sState != 'Empty') {
            $oRegion = $this->regionFactory->create();
            $oRegion->loadByCode(
                $sState,
                $sCountry
            );
            if (!$oRegion->getId()) {// Region not found
                $oRegion = false;
            }
        }
        return $oRegion;
    }

    /**
     * Get region code by address
     *
     * @param  QuoteAddress|OrderAddress $oAddress
     * @return string
     */
    public function getRegionCode($oAddress)
    {
        $sRegionCode = $oAddress->getRegionCode();
        if (strlen($sRegionCode) != 2) {
            $oRegion = $this->regionFactory->create();
            $oRegion->loadByName($sRegionCode, $oAddress->getCountryId());
            if ($oRegion->getId()) {
                $sRegionCode = $oRegion->getCode();
            }
        }
        return $sRegionCode;
    }

    /**
     * Map magento gender to PAYONE gender parameter
     *
     * @param  int $iGender
     * @return string
     */
    public function getGenderParameter($iGender)
    {
        $sGender = '';
        if ($iGender == '1') {
            $sGender = 'm';
        } elseif ($iGender == '2') {
            $sGender = 'f';
        }
        return $sGender;
    }

    /**
     * Map magento gender to PAYONE salutation parameter
     *
     * @param  int $iGender
     * @return string
     */
    public function getSalutationParameter($iGender)
    {
        $sSalutation = '';
        if ($iGender == '1') {
            $sSalutation = (string)__('Mr');
        } elseif ($iGender == '2') {
            $sSalutation = (string)__('Mrs');
        }
        return $sSalutation;
    }
}
