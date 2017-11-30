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
        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('sales_order_status_state'), ['state'])
            ->where("status = :status")
            ->limit(1);
        return $this->getDb()->fetchOne($oSelect, ['status' => $sStatus]);
    }

    /**
     * Get the order increment id by the given TransactionStatus txid
     *
     * @param  string $sTxid
     * @return string
     */
    public function getOrderIncrementIdByTxid($sTxid)
    {
        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('payone_protocol_api'), ['order_id'])
            ->where("txid = :txid")
            ->limit(1);
        return $this->getDb()->fetchOne($oSelect, ['txid' => $sTxid]);
    }

    /**
     * Get module info from db
     *
     * @return array
     */
    public function getModuleInfo()
    {
        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('setup_module'), ['module', 'schema_version'])
            ->where("module NOT LIKE 'Magento_%'");
        return $this->getDb()->fetchAll($oSelect);
    }

    /**
     * Get increment_id from order by given order id
     *
     * @param  string $sOrderId
     * @return string|bool
     */
    public function getIncrementIdByOrderId($sOrderId)
    {
        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('sales_order'), ['increment_id'])
            ->where("entity_id = :entity_id")
            ->limit(1);
        return $this->getDb()->fetchOne($oSelect, ['entity_id' => $sOrderId]);
    }

    /**
     * Get payone user id by given customer nr
     *
     * @param  string $sCustNr
     * @return string
     */
    public function getPayoneUserIdByCustNr($sCustNr)
    {
        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('payone_protocol_transactionstatus'), ['userid'])
            ->where("customerid = :customerid")
            ->limit(1);
        return $this->getDb()->fetchOne($oSelect, ['customerid' => $sCustNr]);
    }

    /**
     * Get the next sequencenumber for this transaction
     *
     * @param  int $iTxid
     * @return int
     */
    public function getSequenceNumber($iTxid)
    {
        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('payone_protocol_transactionstatus'), ['MAX(sequencenumber)'])
            ->where("txid = :txid");
        $iCount = $this->getDb()->fetchOne($oSelect, ['txid' => $iTxid]);
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
        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('core_config_data'), ['value'])
            ->where("path = :path")
            ->where("scope = :scope")
            ->where("scope_id = :scope_id");
        $sPath = $sSection."/".$sGroup."/".$sKey;
        $sScope = ScopeInterface::SCOPE_STORE;
        if (!$sScopeId) {
            $sScopeId = $this->storeManager->getStore()->getId();
        }
        return $this->getDb()->fetchOne($oSelect, ['path' => $sPath, 'scope' => $sScope, 'scope_id' => $sScopeId]);
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

        $oSelect = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('quote_address'), [$sSelectField])
            ->where("firstname = :firstname")
            ->where("lastname = :lastname")
            ->where("street = :street")
            ->where("city = :city")
            ->where("region = :region")
            ->where("postcode = :postcode")
            ->where("country_id = :country_id")
            ->where($sSelectField." != ''")
            ->order('address_id DESC')
            ->limit(1);

        if (!empty($oAddress->getId())) {
            $oSelect->where("address_id != :curr_id");
            $aParams['curr_id'] = $oAddress->getId();
        }
        if (!empty($oAddress->getCustomerId())) {
            $oSelect->where("customer_id = :cust_id");
            $aParams['cust_id'] = $oAddress->getCustomerId();
        }
        if (!empty($oAddress->getAddressType())) {
            $oSelect->where("address_type = :addr_type");
            $aParams['addr_type'] = $oAddress->getAddressType();
        }
        return $this->getDb()->fetchOne($oSelect, $aParams);
    }
}
