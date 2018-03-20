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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\ResourceModel;

/**
 * PaymentBan resource model
 */
class PaymentBan extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('payone_payment_ban', 'id');
    }

    /**
     * Generate date string for end date of the ban
     *
     * @param  int $iHourDuration
     * @return string
     */
    public function getBanEndDate($iHourDuration)
    {
        return date('Y-m-d H:i:s', (time() + (60 * 60 * $iHourDuration)));
    }

    /**
     * Insert new line into payone_payment_ban table
     *
     * @param  string $sPaymentMethod
     * @param  int    $iCustomerId
     * @param  int    $iHourDuration
     * @return $this
     */
    public function addPaymentBan($sPaymentMethod, $iCustomerId, $iHourDuration)
    {
        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'customer_id' => $iCustomerId,
                'payment_method' => $sPaymentMethod,
                'to_date' => $this->getBanEndDate($iHourDuration)
            ]
        );
        return $this;
    }

    /**
     * Get payment bans for the given customer id
     *
     * @param  int $iCustomerId
     * @return array
     */
    public function getPaymentBans($iCustomerId)
    {
        $oSelect = $this->getConnection()->select()
            ->from($this->getMainTable(), ['payment_method', 'to_date'])
            ->where("customer_id = :customerId")
            ->where("to_date > :toDate")
            ->order('to_date ASC');

        $aParams = [
            'customerId' => $iCustomerId,
            'toDate' => date('Y-m-d H:i:s')
        ];

        $aResult = $this->getConnection()->fetchAll($oSelect, $aParams);

        $aReturn = [];
        foreach ($aResult as $aItem) {
            $aReturn[$aItem['payment_method']] = $aItem['to_date'];
        }
        return $aReturn;
    }
}
