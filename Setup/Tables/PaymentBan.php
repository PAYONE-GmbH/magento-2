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
 * Class defining the data needed to create the payone_payment_ban table
 */
class PaymentBan
{
    const TABLE_PAYMENT_BAN = 'payone_payment_ban';

    /**
     * Table data needed to create the new table payone_payment_ban
     *
     * @var array
     */
    protected static $aTableData = [
        'title' => self::TABLE_PAYMENT_BAN,
        'columns' => [
            'id' => [
                'type' => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            ],
            'customer_id' => [
                'type' => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false],
            ],
            'payment_method' => [
                'type' => Table::TYPE_TEXT,
                'length' => 64,
                'option' => [],
            ],
            'from_date' => [
                'type' => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            ],
            'to_date' => [
                'type' => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => false],
            ],
        ],
        'comment' => 'Log the banned payment methods for users'
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
