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

use Payone\Core\Model\Api\Request\Base;

/**
 * ApiLog resource model
 */
class ApiLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Fields that need to be masked before written in to the API log
     *
     * @var array
     */
    protected $aMaskFields = [
        'ip',
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
        $this->_init('payone_protocol_api', 'id');
    }

    /**
     * Get value from array at given key or empty string if not set
     *
     * @param  array       $aRequest
     * @param  string      $sField
     * @param  string|null $sDefault
     * @return string
     */
    protected function getParamValue($aRequest, $sField, $sDefault = null)
    {
        if (isset($aRequest[$sField])) {
            return $aRequest[$sField];
        } elseif ($sDefault !== null) {
            return $sDefault;
        }
        return '';
    }

    /**
     * Mask a given value with Xs
     *
     * @param  string $sValue
     * @return string
     */
    protected function maskValue($sValue)
    {
        for ($i = 0; $i < strlen($sValue); $i++) {
            $sValue[$i] = 'x';
        }
        return $sValue;
    }

    /**
     * Mask certain fields in the request array
     *
     * @param  array $aRequest
     * @return array
     */
    protected function maskParameters($aRequest)
    {
        foreach ($this->aMaskFields as $sKey) {
            if (isset($aRequest[$sKey])) {
                $aRequest[$sKey] = $this->maskValue($aRequest[$sKey]);
            }
        }
        return $aRequest;
    }

    /**
     * Save Api-log entry to database
     *
     * @param  array  $aRequest
     * @param  array  $aResponse
     * @param  string $sStatus
     * @param  string $sOrderId
     * @return $this
     */
    public function addApiLogEntry($aRequest, $aResponse, $sStatus = '', $sOrderId = '')
    {
        $aRequest = $this->maskParameters($aRequest);
        $iTxid = '';
        if (isset($aResponse['txid'])) {
            $iTxid = $aResponse['txid'];
        } elseif (isset($aRequest['txid'])) {
            $iTxid = $aRequest['txid'];
        }

        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'order_id' => $sOrderId,
                'store_id' => $this->shopHelper->getStoreId(),
                'refnr' => $this->getParamValue($aRequest, 'reference'),
                'txid' => $iTxid,
                'requesttype' => $this->getParamValue($aRequest, 'request'),
                'responsestatus' => $sStatus,
                'mid' => $this->getParamValue($aRequest, 'mid'),
                'aid' => $this->getParamValue($aRequest, 'aid', '0'),
                'portalid' => $this->getParamValue($aRequest, 'portalid'),
                'raw_request' => serialize($aRequest),
                'raw_response' => serialize($aResponse),
            ]
        );
        return $this;
    }
}
