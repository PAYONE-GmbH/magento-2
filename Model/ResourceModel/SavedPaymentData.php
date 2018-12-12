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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\ResourceModel;

/**
 * SavedPaymentData resource model
 */
class SavedPaymentData extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Shop helper object
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Encryption method used
     *
     * @var string
     */
    protected $encryptionMethod = 'AES-128-ECB';

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
        $this->_init('payone_saved_payment_data', 'id');
    }

    /**
     * Check if customer has saved data for the given payment method
     *
     * @param  $iCustomerId
     * @param  $sPaymentMethod
     * @return bool
     */
    protected function hasSavedData($iCustomerId, $sPaymentMethod)
    {
        $aSavedPaymentData = $this->getSavedPaymentData($iCustomerId, $sPaymentMethod);
        if (empty($aSavedPaymentData)) {
            return false;
        }
        return true;
    }

    /**
     * Returns the encryption key
     *
     * @return string
     */
    protected function getEncryptionKey()
    {
        return $this->shopHelper->getConfigParam('mid');
    }

    /**
     * Encode and json_encode payment data for db
     *
     * @param  array $aPaymentData
     * @return string
     */
    public function encryptPaymentData($aPaymentData)
    {
        $sPaymentData = json_encode($aPaymentData);
        $sEncryptedData = openssl_encrypt($sPaymentData, $this->encryptionMethod, $this->getEncryptionKey());
        return $sEncryptedData;
    }

    /**
     * Decrypt given and json_decode string
     *
     * @param  string $sEncryptedPaymentData
     * @return array|false
     */
    protected function decryptPaymentData($sEncryptedPaymentData)
    {
        $sDecryptedData = openssl_decrypt($sEncryptedPaymentData, $this->encryptionMethod, $this->getEncryptionKey());
        $aPaymentData = json_decode($sDecryptedData, true);
        if (empty($aPaymentData)) {
            $aPaymentData = false;
        }
        return $aPaymentData;
    }

    /**
     * Check if the given data already exists in the DB
     *
     * @param  array $aData
     * @return bool
     */
    protected function dataAlreadyExists($aData)
    {
        $oSelect = $this->getConnection()->select()
            ->from($this->getMainTable(), ['id'])
            ->where("customer_id = :customerId")
            ->where("payment_method = :paymentMethod")
            ->where("payment_data = :paymentData")
            ->limit(1);

        $aParams = [
            'customerId' => $aData['customer_id'],
            'paymentMethod' => $aData['payment_method'],
            'paymentData' => $aData['payment_data'],
        ];

        $iId = $this->getConnection()->fetchOne($oSelect, $aParams);
        if (!empty($iId)) {
            return true;
        }
        return false;
    }

    /**
     * Insert new line into payone_saved_payment_data table
     *
     * @param int    $iCustomerId
     * @param string $sPaymentMethod
     * @param array  $aPaymentData
     * @return $this
     */
    public function addSavedPaymentData($iCustomerId, $sPaymentMethod, $aPaymentData)
    {
        $aData = [
            'customer_id' => $iCustomerId,
            'payment_method' => $sPaymentMethod,
            'is_default' => $this->hasSavedData($iCustomerId, $sPaymentMethod) === true ? '0' : '1',
            'payment_data' => $this->encryptPaymentData($aPaymentData),
        ];

        if ($this->dataAlreadyExists($aData) === false) {
            $this->getConnection()->insert($this->getMainTable(), $aData);
        }
        return $this;
    }

    /**
     * Format single data entry
     *
     * @param  array $aData
     * @return array
     */
    protected function formatData(&$aData)
    {
        if (!empty($aData['payment_data'])) {
            $aData['payment_data'] = $this->decryptPaymentData($aData['payment_data']);
        }
        return $aData;
    }

    /**
     * Get saved payment data for customer
     *
     * @param  int         $iCustomerId
     * @param  string|bool $sPaymentMethod
     * @return array
     */
    public function getSavedPaymentData($iCustomerId, $sPaymentMethod = false)
    {
        if (!$iCustomerId) {
            return [];
        }

        $oSelect = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where("customer_id = :customerId")
            ->order(['is_default DESC', 'created_at ASC']);

        $aParams = ['customerId' => $iCustomerId];

        if ($sPaymentMethod !== false) {
            $oSelect->where("payment_method = :paymentMethod");
            $aParams['paymentMethod'] = $sPaymentMethod;
        }

        $aReturn = [];

        $aResult = $this->getConnection()->fetchAll($oSelect, $aParams);
        foreach ($aResult as $aRow) {
            $aRow = $this->formatData($aRow);
            if (!empty($aRow['payment_data'])) {
                $aReturn[] = $aRow;
            }
        }
        return $aReturn;
    }

    /**
     * Delete the entity of the given id
     *
     * @param  int         $iId
     * @param  int         $iCustomerId
     * @param  string|bool $sPaymentMethod
     * @return void
     */
    public function deletePaymentData($iId, $iCustomerId, $sPaymentMethod = false)
    {
        $this->getConnection()->delete($this->getMainTable(), ['id = ?' => $iId]);

        $aAllRows = $this->getSavedPaymentData($iCustomerId, $sPaymentMethod);
        if (!empty($aAllRows)) {
            $aFirstRow = current($aAllRows);
            $this->setDefault($aFirstRow['id'], $iCustomerId);
        }
    }

    /**
     * Set given id as default payment for the given customer
     *
     * @param  int $iId
     * @param  int $iCustomerId
     * @return void
     */
    public function setDefault($iId, $iCustomerId)
    {
        $data = ['is_default' => 0];
        $where = ['customer_id = ?' => $iCustomerId];
        $this->getConnection()->update($this->getMainTable(), $data, $where);

        $data = ['is_default' => 1];
        $where = ['id = ?' => $iId];
        $this->getConnection()->update($this->getMainTable(), $data, $where);
    }
}
