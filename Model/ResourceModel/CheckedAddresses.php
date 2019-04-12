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

namespace Payone\Core\Model\ResourceModel;

use Magento\Quote\Api\Data\AddressInterface;

/**
 * CheckedAddresses resource model
 */
class CheckedAddresses extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Shop helper object
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

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
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Payone\Core\Helper\Shop $shopHelper
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Payone\Core\Helper\Shop $shopHelper,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->shopHelper = $shopHelper;
    }

    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('payone_checked_addresses', 'address_hash');
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
     * @param  array|bool      $aResponse
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
     * Insert checked address into database
     *
     * @param string $sHash
     * @param bool $blIsBonicheck
     * @param string $sChecktype
     * @return void
     */
    protected function insertCheckedAddress($sHash, $blIsBonicheck, $sChecktype)
    {
        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'address_hash' => $sHash,
                'is_bonicheck' => $blIsBonicheck,
                'checktype' => $sChecktype,
            ]
        );
    }

    /**
     * Handle saving of checked addresses
     *
     * @param  AddressInterface $oAddress
     * @param  array            $aResponse
     * @param  string           $sChecktype
     * @param  bool             $blIsBonicheck
     * @return $this
     */
    public function addCheckedAddress(AddressInterface $oAddress, $aResponse, $sChecktype, $blIsBonicheck = false)
    {
        $sHash = $this->getHashFromAddress($oAddress, $aResponse); // generate hash from given address
        $this->insertCheckedAddress($sHash, $blIsBonicheck, $sChecktype);

        error_log(date('Y-m-d H:i:s - ')."A1 - Added checked address Hash: '".$sHash."'\n", 3, dirname(__FILE__).'/../../../../../../MAG2_74.log');

        $sUncorrectedHash = $this->getHashFromAddress($oAddress);
        if ($sHash !== $sUncorrectedHash) {
            $this->insertCheckedAddress($sUncorrectedHash, $blIsBonicheck, $sChecktype);
            error_log(date('Y-m-d H:i:s - ')."A2 - Added checked address Uncorrected Hash: '".$sUncorrectedHash."'\n", 3, dirname(__FILE__).'/../../../../../../MAG2_74.log');
        }
        return $this;
    }

    /**
     * Check and return if this exact address has been checked before
     *
     * @param  AddressInterface $oAddress
     * @param  string           $sChecktype
     * @param  bool             $blIsBonicheck
     * @return bool
     */
    public function wasAddressCheckedBefore(AddressInterface $oAddress, $sChecktype, $blIsBonicheck = false)
    {
        $sGroup = 'address_check';
        if ($blIsBonicheck === true) {
            $sGroup = 'creditrating';
        }

        $sDebugPath = '../../../../../../';
        error_log("###############################################################\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');
        error_log(date('Y-m-d H:i:s - ')."1 - wasAddressCheckedBefore - Start\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');
        $sLifetime = $this->shopHelper->getConfigParam('result_lifetime', $sGroup, 'payone_protect');
        error_log(date('Y-m-d H:i:s - ')."2 - Lifetime config param: '".$sLifetime."'\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');
        if (empty($sLifetime) || !is_numeric($sLifetime)) {
            error_log(date('Y-m-d H:i:s - ')."3 - Empty or not numeric - CHECK\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');
            return false; // no lifetime = check every time
        }
        error_log(date('Y-m-d H:i:s - ')."4 - Not empty and numeric - go further\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');

        $oSelect = $this->getConnection()->select()
            ->from($this->getMainTable(), ['checkdate'])
            ->where("address_hash = :hash")
            ->where("is_bonicheck = :isBoni")
            ->where("checktype = :checkType")
            ->where('checkdate > DATE_SUB(NOW(), INTERVAL :lifetime DAY)');

        $aParams = [
            'hash' => $this->getHashFromAddress($oAddress),
            'isBoni' => $blIsBonicheck,
            'checkType' => $sChecktype,
            'lifetime' => $sLifetime
        ];

        error_log(date('Y-m-d H:i:s - ')."5 - SQL assembled: ".$oSelect->assemble()."\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');
        error_log(date('Y-m-d H:i:s - ').print_r($aParams, true)."\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');

        $sDate = $this->getConnection()->fetchOne($oSelect, $aParams);

        $oDebugSelect = $this->getConnection()->select()->from($this->getMainTable())->where("address_hash = :hash")->where("is_bonicheck = :isBoni")->where("checktype = :checkType");
        unset($aParams['lifetime']);

        if ($sDate != false) {
            error_log(date('Y-m-d H:i:s - ')."6 - Got date: ".$sDate." - address was checked before - DONT CHECK\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');
            error_log(date('Y-m-d H:i:s - ')."6b - DebugSelect: ".print_r($this->getConnection()->fetchAll($oDebugSelect, $aParams), true)."\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');

            return true;
        }
        error_log(date('Y-m-d H:i:s - ')."7 - Got no date: ".$sDate." - address was not checked before ?!? - CHECK\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');
        error_log(date('Y-m-d H:i:s - ')."8b - DebugSelect: ".print_r($this->getConnection()->fetchAll($oDebugSelect, $aParams), true)."\n", 3, dirname(__FILE__).'/'.$sDebugPath.'MAG2_74.log');


        return false;
    }
}
