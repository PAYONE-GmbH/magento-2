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
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Payone\Core\Helper\Checkout $checkoutHelper
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Payone\Core\Helper\Checkout $checkoutHelper,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->checkoutHelper = $checkoutHelper;
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
     * Save Api-log entry to database
     *
     * @param  AddressInterface $oAddress
     * @param  array            $aResponse
     * @param  string           $sChecktype
     * @param  bool             $blIsBonicheck
     * @return $this
     */
    public function addCheckedAddress(AddressInterface $oAddress, $aResponse, $sChecktype, $blIsBonicheck = false)
    {
        $sHash = $this->checkoutHelper->getHashFromAddress($oAddress, $aResponse); // generate hash from given address
        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'address_hash' => $sHash,
                'is_bonicheck' => $blIsBonicheck,
                'checktype' => $sChecktype,
                'score' => isset($aResponse['score']) ? $aResponse['score'] : ''
            ]
        );
        return $this;
    }

    /**
     * Get lifetime config
     *
     * @param  string $sConfigField
     * @param  bool   $blIsBonicheck
     * @return string
     */
    protected function getConfigValue($sConfigField, $blIsBonicheck)
    {
        $sGroup = 'address_check';
        if ($blIsBonicheck === true) {
            $sGroup = 'creditrating';
        }
        return $this->checkoutHelper->getConfigParam($sConfigField, $sGroup, 'payone_protect');
    }

    /**
     * Executes a select on the checked addresses table
     *
     * @param  AddressInterface $oAddress
     * @param  string           $sChecktype
     * @param  bool             $blIsBonicheck
     * @param  string           $sLifetime
     * @param  array            $aSelectFields
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function selectAddressesChecked(AddressInterface $oAddress, $sChecktype, $blIsBonicheck, $sLifetime, $aSelectFields)
    {
        $oSelect = $this->getConnection()->select()
            ->from($this->getMainTable(), $aSelectFields)
            ->where("address_hash = :hash")
            ->where("is_bonicheck = :isBoni")
            ->where("checktype = :checkType")
            ->where('checkdate > DATE_SUB(NOW(), INTERVAL :lifetime DAY)');

        $aParams = [
            'hash' => $this->checkoutHelper->getHashFromAddress($oAddress),
            'isBoni' => $blIsBonicheck,
            'checkType' => $sChecktype,
            'lifetime' => $sLifetime
        ];

        return $this->getConnection()->fetchOne($oSelect, $aParams);
    }

    /**
     * Returns score for the given address
     *
     * @param AddressInterface $oAddress
     * @param bool $blIsBonicheck
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLatestScoreForAddress(AddressInterface $oAddress, $blIsBonicheck)
    {
        $sLifetime = $this->getConfigValue('result_lifetime', $blIsBonicheck);
        $sChecktype = $this->getConfigValue('type', $blIsBonicheck);
        return $this->selectAddressesChecked($oAddress, $sChecktype, $blIsBonicheck, $sLifetime, ['score']);
    }

    /**
     * Check and return if this exact address has been checked before
     *
     * @param  AddressInterface $oAddress
     * @param  string           $sChecktype
     * @param  bool             $blIsBonicheck
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function wasAddressCheckedBefore(AddressInterface $oAddress, $sChecktype, $blIsBonicheck = false)
    {
        $sLifetime = $this->getConfigValue('result_lifetime', $blIsBonicheck);
        if (empty($sLifetime) || !is_numeric($sLifetime)) {
            return false; // no lifetime = check every time
        }

        $sDate = $this->selectAddressesChecked($oAddress, $sChecktype, $blIsBonicheck, $sLifetime, ['checkdate']);
        if ($sDate != false) {
            return true;
        }
        return false;
    }
}
