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

namespace Payone\Core\Setup\Tables;

use Magento\Framework\DB\Ddl\Table;

/**
 * Class defining the data needed to create the payone_protocol_api table
 */
class Transactionstatus
{
    const TABLE_PROTOCOL_TRANSACTIONSTATUS = 'payone_protocol_transactionstatus';

    /**
     * Table data needed to create the new table payone_protocol_transactionstatus
     *
     * @var array
     */
    protected static $aTableData = [
        'title' => self::TABLE_PROTOCOL_TRANSACTIONSTATUS,
        'columns' => [
            'id' => [
                'type' => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            ],
            'timestamp' => [
                'type' => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => false, 'default' => Table::TIMESTAMP_INIT]
            ],
            'order_id' => [
                'type' => Table::TYPE_TEXT,
                'length' => 50,
                'option' => ['unsigned' => true, 'nullable' => false]
            ],
            'store_id' => [
                'type' => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false]
            ],
            'reference' => ['type' => Table::TYPE_TEXT, 'length' => 20, 'option' => []],
            'txid' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'txaction' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'sequencenumber' => [
                'type' => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false]
            ],
            'clearingtype' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'txtime' => [
                'type' => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => false]
            ],
            'price' => [
                'type' => Table::TYPE_FLOAT,
                'length' => null,
                'option' => ['default' => '0']
            ],
            'balance' => [
                'type' => Table::TYPE_FLOAT,
                'length' => null,
                'option' => ['default' => '0']
            ],
            'receivable' => [
                'type' => Table::TYPE_FLOAT,
                'length' => null,
                'option' => ['default' => '0']
            ],
            'currency' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'aid' => [
                'type' => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false]
            ],
            'portalid' => [
                'type' => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false]
            ],
            'key' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'mode' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'userid' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'customerid' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'company' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'firstname' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'lastname' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'street' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'zip' => ['type' => Table::TYPE_TEXT, 'length' => 16, 'option' => []],
            'city' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'email' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'country' => ['type' => Table::TYPE_TEXT, 'length' => 8, 'option' => []],
            'shipping_company' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'shipping_firstname' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'shipping_lastname' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'shipping_street' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'shipping_zip' => ['type' => Table::TYPE_TEXT, 'length' => 16, 'option' => []],
            'shipping_city' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'shipping_country' => ['type' => Table::TYPE_TEXT, 'length' => 8, 'option' => []],
            'param' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'accessname' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'accesscode' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'bankcountry' => ['type' => Table::TYPE_TEXT, 'length' => 8, 'option' => []],
            'bankaccount' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'bankcode' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'bankaccountholder' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'cardexpiredate' => ['type' => Table::TYPE_TEXT, 'length' => 8, 'option' => []],
            'cardtype' => ['type' => Table::TYPE_TEXT, 'length' => 8, 'option' => []],
            'cardpan' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'clearing_bankaccountholder' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'clearing_bankaccount' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'clearing_bankcode' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => [],],
            'clearing_bankname' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'clearing_bankbic' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'clearing_bankiban' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'clearing_legalnote' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'clearing_duedate' => ['type' => Table::TYPE_TEXT, 'length' => 32, 'option' => []],
            'clearing_reference' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'clearing_instructionnote' => ['type' => Table::TYPE_TEXT, 'length' => 255, 'option' => []],
            'raw_status' => ['type' => Table::TYPE_TEXT, 'length' => null, 'option' => []],
        ],
        'comment' => 'Log every TransactionStatus from Payone',
        'indexes' => ['order_id', 'store_id']
    ];

    /**
     * Return the table data needed to create this table
     *
     * @return array
     */
    public static function getData()
    {
        return self::$aTableData;
    }
}
