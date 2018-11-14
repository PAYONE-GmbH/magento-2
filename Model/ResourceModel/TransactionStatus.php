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

use Payone\Core\Helper\Toolkit;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;

/**
 * TransactionStatus resource model
 */
class TransactionStatus extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * TransactionStatus POST request
     *
     * @var array
     */
    protected $request = [];

    /**
     * Store manager object
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Toolkit helper object
     *
     * @var Toolkit
     */
    protected $toolkitHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Payone\Core\Helper\Toolkit                       $toolkitHelper
     * @param string                                            $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->storeManager = $storeManager;
        $this->toolkitHelper = $toolkitHelper;
    }

    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('payone_protocol_transactionstatus', 'id');
    }

    /**
     * Set request property
     *
     * @param  array $request
     * @return void
     */
    public function setRequest($request)
    {
        if (is_array($request)) {
            $this->request = $request;
        }
    }

    /**
     * Get request property
     *
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get request-parameter value or a given default if not set
     *
     * @param  string $sKey
     * @param  string $sDefault
     * @return string
     */
    public function getParam($sKey, $sDefault = '')
    {
        if (isset($this->request[$sKey])) {
            $sParam = $this->request[$sKey];
            if (!$this->toolkitHelper->isUTF8($sParam)) {
                $sParam = utf8_encode($sParam);
            }
            return $sParam;
        }
        return $sDefault;
    }

    /**
     * Write TransactionStatus entry to database
     *
     * @param  array $aRequest
     * @param  Order $oOrder
     * @param  bool  $blHasBeenHandled
     * @return $this
     */
    public function addTransactionLogEntry($aRequest, Order $oOrder = null, $blHasBeenHandled = true)
    {
        $this->setRequest($aRequest);

        $sRawStatus = serialize($this->getRequest());
        if (!$this->toolkitHelper->isUTF8($sRawStatus)) {
            $sRawStatus = utf8_encode($sRawStatus); // needed for serializing the array
        }
        $sOrderId = $oOrder !== null ? $oOrder->getIncrementId() : '';
        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'order_id' => $sOrderId,
                'store_id' => $this->storeManager->getStore()->getId(),
                'reference' => $this->getParam('reference'),
                'txid' => $this->getParam('txid'),
                'txaction' => $this->getParam('txaction'),
                'sequencenumber' => $this->getParam('sequencenumber'),
                'clearingtype' => $this->getParam('clearingtype'),
                'txtime' => date('Y-m-d H:i:s', $this->getParam('txtime')),
                'price' => $this->getParam('price'),
                'balance' => $this->getParam('balance'),
                'receivable' => $this->getParam('receivable'),
                'currency' => $this->getParam('currency'),
                'aid' => $this->getParam('aid'),
                'portalid' => $this->getParam('portalid'),
                'key' => $this->getParam('key'),
                'mode' => $this->getParam('mode'),
                'userid' => $this->getParam('userid'),
                'customerid' => $this->getParam('customerid'),
                'company' => $this->getParam('company'),
                'firstname' => $this->getParam('firstname'),
                'lastname' => $this->getParam('lastname'),
                'street' => $this->getParam('street'),
                'zip' => $this->getParam('zip'),
                'city' => $this->getParam('city'),
                'email' => $this->getParam('email'),
                'country' => $this->getParam('country'),
                'shipping_company' => $this->getParam('shipping_company'),
                'shipping_firstname' => $this->getParam('shipping_firstname'),
                'shipping_lastname' => $this->getParam('shipping_lastname'),
                'shipping_street' => $this->getParam('shipping_street'),
                'shipping_zip' => $this->getParam('shipping_zip'),
                'shipping_city' => $this->getParam('shipping_city'),
                'shipping_country' => $this->getParam('shipping_country'),
                'param' => $this->getParam('param'),
                'accessname' => $this->getParam('accessname'),
                'accesscode' => $this->getParam('accesscode'),
                'bankcountry' => $this->getParam('bankcountry'),
                'bankaccount' => $this->getParam('bankaccount'),
                'bankcode' => $this->getParam('bankcode'),
                'bankaccountholder' => $this->getParam('bankaccountholder'),
                'cardexpiredate' => $this->getParam('cardexpiredate'),
                'cardtype' => $this->getParam('cardtype'),
                'cardpan' => $this->getParam('cardpan'),
                'clearing_bankaccountholder' => $this->getParam('clearing_bankaccountholder'),
                'clearing_bankaccount' => $this->getParam('clearing_bankaccount'),
                'clearing_bankcode' => $this->getParam('clearing_bankcode'),
                'clearing_bankname' => $this->getParam('clearing_bankname'),
                'clearing_bankbic' => $this->getParam('clearing_bankbic'),
                'clearing_bankiban' => $this->getParam('clearing_bankiban'),
                'clearing_legalnote' => $this->getParam('clearing_legalnote'),
                'clearing_duedate' => $this->getParam('clearing_duedate'),
                'clearing_reference' => $this->getParam('clearing_reference'),
                'clearing_instructionnote' => $this->getParam('clearing_instructionnote'),
                'raw_status' => $sRawStatus,
                'has_been_handled' => $blHasBeenHandled
            ]
        );
        return $this;
    }

    /**
     * Get TransactionStatus id be txid
     *
     * @param  string $sTxid
     * @return string
     */
    public function getAppointedIdByTxid($sTxid)
    {
        $oConn = $this->getConnection();

        $oSelect = $oConn->select()->from($this->getMainTable())
            ->where('txid = :txid')
            ->where('txaction = :txaction');

        return $oConn->fetchOne($oSelect, ['txid' => $sTxid, 'txaction' => 'appointed']);
    }
}
