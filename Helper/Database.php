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
     * @param  \Magento\Framework\App\Helper\Context              $context
     * @param  \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param  \Magento\Framework\App\ResourceConnection          $resource
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context, $storeManager, $scopeConfig);
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
        $oDb = $this->getDb();
        $sQuery = " SELECT
                        state
                    FROM
                        ".$oDb->getTableName('sales_order_status_state')."
                    WHERE
                        status = :status
                    LIMIT 1";
        $sState = $oDb->fetchOne($sQuery, ['status' => $sStatus]);
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
        $oDb = $this->getDb();
        $sQuery = " SELECT
                        order_id
                    FROM
                        ".$oDb->getTableName('payone_protocol_api')."
                    WHERE
                        txid = :txid
                    LIMIT 1";
        $sIncrementId = $oDb->fetchOne($sQuery, ['txid' => $sTxid]);
        return $sIncrementId;
    }

    /**
     * Get module info from db
     *
     * @return array
     */
    public function getModuleInfo()
    {
        $oDb = $this->getDb();
        $sQuery = " SELECT
                        module, schema_version
                    FROM
                        ".$oDb->getTableName('setup_module')."
                    WHERE
                        module NOT LIKE 'Magento_%'";
        $aResult = $oDb->fetchAll($sQuery);
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
        $oDb = $this->getDb();
        $sQuery = " SELECT
                        *
                    FROM
                        ".$oDb->getTableName('sales_order')."
                    WHERE
                        entity_id = :entity_id";
        $aResult = $oDb->fetchRow($sQuery, ['entity_id' => $sOrderId]);
        if ($aResult) {
            return $aResult['increment_id'];
        }
        return false;
    }

    /**
     * Get payone user id by given customer nr
     *
     * @param  string $sCustNr
     * @return string
     */
    public function getPayoneUserIdByCustNr($sCustNr)
    {
        $oDb = $this->getDb();
        $sQuery = " SELECT
                        userid
                    FROM
                        ".$oDb->getTableName('payone_protocol_transactionstatus')."
                    WHERE
                        customerid = :customerid
                    LIMIT 1";
        $sUserId = $oDb->fetchOne($sQuery, ['customerid' => $sCustNr]);
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
        $oDb = $this->getDb();
        $sQuery = " SELECT
                        MAX(sequencenumber)
                    FROM
                        {$oDb->getTableName('payone_protocol_transactionstatus')}
                    WHERE
                        txid = :txid";
        $iCount = $oDb->fetchOne($sQuery, ['txid' => $iTxid]);
        if ($iCount === null) {
            return 0;
        }
        return $iCount+1;
    }
}
