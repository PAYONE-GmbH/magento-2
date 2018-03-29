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

namespace Payone\Core\Model\Api\Request;

use Payone\Core\Helper\Country as CountryHelper;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Sales\Model\Order\Address as OrderAddress;

/**
 * Base class for all PAYONE requests that need the address methods
 */
abstract class AddressRequest extends Base
{
    /**
     * PAYONE customer helper
     *
     * @var \Payone\Core\Helper\Customer
     */
    protected $customerHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog);
        $this->customerHelper = $customerHelper;
    }

    /**
     * Add address-parameters to the request
     *
     * @param  QuoteAddress|OrderAddress $oAddress
     * @param  bool                      $blIsShipping
     * @return void
     */
    protected function addAddress($oAddress, $blIsShipping = false)
    {
        $sPre = '';
        if ($blIsShipping === true) {
            $sPre = 'shipping_'; // add shipping prefix for shipping addresses
        }
        $this->addParameter($sPre.'firstname', $oAddress->getFirstname());
        $this->addParameter($sPre.'lastname', $oAddress->getLastname());
        if ($oAddress->getCompany()) {// company name existing?
            $this->addParameter($sPre.'company', $oAddress->getCompany());
        }

        $aStreet = $oAddress->getStreet();
        $sStreet = is_array($aStreet) ? implode(' ', $aStreet) : $aStreet; // street may be an array
        $this->addParameter($sPre.'street', trim($sStreet));
        $this->addParameter($sPre.'zip', $oAddress->getPostcode());
        $this->addParameter($sPre.'city', $oAddress->getCity());
        $this->addParameter($sPre.'country', $oAddress->getCountryId());

        if (CountryHelper::isStateNeeded($oAddress->getCountryId()) && $oAddress->getRegionCode()) {
            $this->addParameter($sPre.'state', $this->customerHelper->getRegionCode($oAddress));
        }
    }

    /**
     * Add user-data to the request
     *
     * @param  QuoteAddress|OrderAddress $oBilling
     * @param  PayoneMethod              $oPayment
     * @param  string                    $iGender
     * @param  string                    $sEmail
     * @param  string                    $sDob
     * @param  bool                      $blIsUpdateUser
     * @return void
     */
    protected function addUserDataParameters($oBilling, PayoneMethod $oPayment, $iGender, $sEmail, $sDob, $blIsUpdateUser = false)
    {
        $this->addAddress($oBilling);

        if ($iGender || $blIsUpdateUser) {
            $this->addParameter('salutation', $this->customerHelper->getSalutationParameter($iGender), $blIsUpdateUser);
            $this->addParameter('gender', $this->customerHelper->getGenderParameter($iGender), $blIsUpdateUser);
        }

        $this->addParameter('email', $sEmail);
        if ($blIsUpdateUser || $oBilling->getTelephone()) {
            $this->addParameter('telephonenumber', $oBilling->getTelephone(), $blIsUpdateUser);
        }

        if ((
                in_array($oPayment->getCode(), [PayoneConfig::METHOD_KLARNA]) &&
                in_array($oBilling->getCountryId(), ['DE', 'NL', 'AT'])
            ) || ($blIsUpdateUser || ($sDob != '0000-00-00 00:00:00' && $sDob != ''))
        ) {
            $this->addParameter('birthday', str_replace('-', '', date('Ymd', strtotime($sDob)), $blIsUpdateUser));
        }

        $this->addParameter('language', $this->shopHelper->getLocale());
        if ($blIsUpdateUser || $oBilling->getVatId() != '') {
            $this->addParameter('vatid', $oBilling->getVatId(), $blIsUpdateUser);
        }
    }
}
