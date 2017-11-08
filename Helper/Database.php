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

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Helper class for everything that has to do with database connections
 */
class Database extends \Payone\Core\Helper\Base
{
    /**
     * Database connection resource
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $databaseResource;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Framework\App\ResourceConnection  $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->databaseResource = $resource;
    }

    /**
     * Return database connection
     *
     * @return AdapterInterface
     */
    protected function getDb()
    {
        return $this->databaseResource->getConnection();
    }

    /**
     * Get the state for a given status from the database
     *
     * @param  string $sStatus
     * @return string
     */
    public function getStateByStatus($sStatus)
    {
        $sQuery = " SELECT
                        state
                    FROM
                        ".$this->databaseResource->getTableName('sales_order_status_state')."
                    WHERE
                        status = :status
                    LIMIT 1";
        $sState = $this->getDb()->fetchOne($sQuery, ['status' => $sStatus]);
        return $sState;
    }

    /**
     * Get the order increment id by the given TransactionStatus txid
     *
     * @param  string $sTxid
     * @return string
     */
    public function getOrderIncrementIdByTxid($sTxid)
    {
        $sQuery = " SELECT
                        order_id
                    FROM
                        ".$this->databaseResource->getTableName('payone_protocol_api')."
                    WHERE
                        txid = :txid
                    LIMIT 1";
        $sIncrementId = $this->getDb()->fetchOne($sQuery, ['txid' => $sTxid]);
        return $sIncrementId;
    }

    /**
     * Get module info from db
     *
     * @return array
     */
    public function getModuleInfo()
    {
        $sQuery = " SELECT
                        module, schema_version
                    FROM
                        ".$this->databaseResource->getTableName('setup_module')."
                    WHERE
                        module NOT LIKE 'Magento_%'";
        $aResult = $this->getDb()->fetchAll($sQuery);
        return $aResult;
    }

    /**
     * Get increment_id from order by given order id
     *
     * @param  string $sOrderId
     * @return string|bool
     */
    public function getIncrementIdByOrderId($sOrderId)
    {
        $sQuery = " SELECT
                        increment_id
                    FROM
                        ".$this->databaseResource->getTableName('sales_order')."
                    WHERE
                        entity_id = :entity_id";
        $sIncrementId = $this->getDb()->fetchOne($sQuery, ['entity_id' => $sOrderId]);
        return $sIncrementId;
    }

    /**
     * Get payone user id by given customer nr
     *
     * @param  string $sCustNr
     * @return string
     */
    public function getPayoneUserIdByCustNr($sCustNr)
    {
        $sQuery = " SELECT
                        userid
                    FROM
                        ".$this->databaseResource->getTableName('payone_protocol_transactionstatus')."
                    WHERE
                        customerid = :customerid
                    LIMIT 1";
        $sUserId = $this->getDb()->fetchOne($sQuery, ['customerid' => $sCustNr]);
        return $sUserId;
    }

    /**
     * Get the next sequencenumber for this transaction
     *
     * @param  int $iTxid
     * @return int
     */
    public function getSequenceNumber($iTxid)
    {
        $sQuery = " SELECT
                        MAX(sequencenumber)
                    FROM
                        ".$this->databaseResource->getTableName('payone_protocol_transactionstatus')."
                    WHERE
                        txid = :txid";
        $iCount = $this->getDb()->fetchOne($sQuery, ['txid' => $iTxid]);
        if ($iCount === null) {
            return 0;
        }
        return $iCount + 1;
    }

    /**
     * Helper method to get parameter from the config directly from db without cache
     *
     * @param  string $sKey
     * @param  string $sGroup
     * @param  string $sSection
     * @param  string $sScopeId
     * @return string
     */
    public function getConfigParamWithoutCache($sKey, $sGroup = 'global', $sSection = 'payone_general', $sScopeId = null)
    {
        $sQuery = " SELECT
                        value
                    FROM
                        ".$this->databaseResource->getTableName('core_config_data')."
                    WHERE
                        path = :path AND
                        scope = :scope AND
                        scope_id = :scope_id";
        $sPath = $sSection."/".$sGroup."/".$sKey;
        $sScope = ScopeInterface::SCOPE_STORE;
        if (!$sScopeId) {
            $sScopeId = $this->storeManager->getStore()->getId();
        }
        $sReturn = $this->getDb()->fetchOne($sQuery, ['path' => $sPath, 'scope' => $sScope, 'scope_id' => $sScopeId]);
        return $sReturn;
    }

    /**
     * Get the address status from a previous order address
     *
     * @param  AddressInterface $oAddress
     * @param  bool             $blIsCreditrating
     * @return string
     */
    public function getOldAddressStatus(AddressInterface $oAddress, $blIsCreditrating = true)
    {
        $sSelectField = 'payone_protect_score';
        if ($blIsCreditrating === false) {
            $sSelectField = 'payone_addresscheck_score';
        }

        $aParams = [
            'firstname' => $oAddress->getFirstname(),
            'lastname' => $oAddress->getLastname(),
            'street' => $oAddress->getStreet()[0],
            'city' => $oAddress->getCity(),
            'region' => $oAddress->getRegion(),
            'postcode' => $oAddress->getPostcode(),
            'country_id' => $oAddress->getCountryId(),
        ];
        $sQuery = " SELECT
                        {$sSelectField}
                    FROM
                        {$this->databaseResource->getTableName('quote_address')}
                    WHERE
                        firstname = :firstname AND
                        lastname = :lastname AND
                        street = :street AND
                        city = :city AND
                        region = :region AND
                        postcode = :postcode AND
                        country_id = :country_id";
        if (!empty($oAddress->getId())) {
            $sQuery .= " AND address_id != :curr_id";
            $aParams['curr_id'] = $oAddress->getId();
        }
        if (!empty($oAddress->getCustomerId())) {
            $sQuery .= " AND customer_id = :cust_id";
            $aParams['cust_id'] = $oAddress->getCustomerId();
        }
        if (!empty($oAddress->getAddressType())) {
            $sQuery .= " AND address_type = :addr_type";
            $aParams['addr_type'] = $oAddress->getAddressType();
        }
        if ($blIsCreditrating === true) {
            $sQuery .= " AND payone_protect_score != ''";
        } else {
            $sQuery .= " AND payone_addresscheck_score != ''";
        }
        $sQuery .= " ORDER BY address_id DESC LIMIT 1";

        $sReturn = $this->getDb()->fetchOne($sQuery, $aParams);
        return $sReturn;
    }
}
