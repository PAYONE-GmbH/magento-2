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

namespace Payone\Core\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Payone\Core\Setup\Tables\Api;
use Payone\Core\Setup\Tables\PaymentBan;
use Payone\Core\Setup\Tables\Transactionstatus;
use Payone\Core\Setup\Tables\SavedPaymentData;
use Payone\Core\Setup\Tables\RatepayProfileConfig;

/**
 * Magento script for updating the database after the initial installation
 */
class UpgradeSchema extends BaseSchema implements UpgradeSchemaInterface
{
    /**
     * Add new columns
     *
     * @param  SchemaSetupInterface $setup
     * @param  ModuleContextInterface $context
     * @return void
     */
    protected function addNewColumns(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!$setup->getConnection()->tableColumnExists($setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS), 'has_been_handled')) {
            $setup->getConnection()->addColumn(
                $setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS),
                'has_been_handled',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'length' => null,
                    'nullable' => false,
                    'default' => 1,
                    'comment' => 'Has the status been handled already'
                ]
            );
        }

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('customer_entity'), 'payone_paydirekt_registered')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('customer_entity'),
                'payone_paydirekt_registered',
                [
                    'type' => Table::TYPE_INTEGER,
                    'length' => 1,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Payone paydirekt OneClick is registered'
                ]
            );
        }


        if (!$setup->getConnection()->tableColumnExists($setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS), 'clearing_bankcity')) {
            $setup->getConnection()->addColumn(
                $setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS),
                'clearing_bankcity',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 64,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Clearing bank country',
                    'after' => 'clearing_bankiban',
                ]
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS), 'clearing_bankcountry')) {
            $setup->getConnection()->addColumn(
                $setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS),
                'clearing_bankcountry',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 32,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Clearing bank country',
                    'after' => 'clearing_bankiban',
                ]
            );
        }
    }

    /**
     * Add new tables
     *
     * @param  SchemaSetupInterface $setup
     * @param  ModuleContextInterface $context
     * @return void
     */
    protected function addNewTables(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!$setup->getConnection()->isTableExists($setup->getTable(PaymentBan::TABLE_PAYMENT_BAN))) {
            $this->addTable($setup, PaymentBan::getData());
        }
        if (!$setup->getConnection()->isTableExists($setup->getTable(SavedPaymentData::TABLE_SAVED_PAYMENT_DATA))) {
            $this->addTable($setup, SavedPaymentData::getData());
        }
        if (!$setup->getConnection()->isTableExists($setup->getTable(RatepayProfileConfig::TABLE_RATEPAY_PROFILE_CONFIG))) {
            $this->addTable($setup, RatepayProfileConfig::getData());
        }
    }

    /**
     * Modify already existing columns
     *
     * @param  SchemaSetupInterface $setup
     * @param  ModuleContextInterface $context
     * @return void
     */
    protected function modifyColumns(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.3.0', '<=')) {
            $setup->getConnection()->modifyColumn(
                $setup->getTable('payone_protocol_api'),
                'mid', ['type' => Table::TYPE_INTEGER, 'default' => '0']
            );
            $setup->getConnection()->modifyColumn(
                $setup->getTable('payone_protocol_api'),
                'aid', ['type' => Table::TYPE_INTEGER, 'default' => '0']
            );
            $setup->getConnection()->modifyColumn(
                $setup->getTable('payone_protocol_api'),
                'portalid', ['type' => Table::TYPE_INTEGER, 'default' => '0']
            );
        }

        if (version_compare($context->getVersion(), '2.5.1', '<')) {
            $setup->getConnection()->modifyColumn(
                $setup->getTable('payone_protocol_transactionstatus'),
                'aid',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'default' => '0'
                ]
            );
            $setup->getConnection()->modifyColumn(
                $setup->getTable('payone_protocol_transactionstatus'),
                'portalid',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'default' => '0'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.5.2', '<')) {
            // Magento 2.3.0 changed Table::TYPE_FLOAT to have no decimals.. so type has to be changed
            $setup->getConnection()->modifyColumn(
                $setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS),
                'price', ['type' => Table::TYPE_DECIMAL, 'length' => '20,4', 'default' => '0']
            );
            $setup->getConnection()->modifyColumn(
                $setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS),
                'balance', ['type' => Table::TYPE_DECIMAL, 'length' => '20,4', 'default' => '0']
            );
            $setup->getConnection()->modifyColumn(
                $setup->getTable(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS),
                'receivable', ['type' => Table::TYPE_DECIMAL, 'length' => '20,4', 'default' => '0']
            );
        }

        if (version_compare($context->getVersion(), '3.4.2', '<')) {
            $setup->getConnection()->modifyColumn(
                $setup->getTable(RatepayProfileConfig::TABLE_RATEPAY_PROFILE_CONFIG),
                'month_allowed', ['type' => Table::TYPE_TEXT, 'length' => '255', 'default' => null]
            );
        }
    }

    /**
     * Add indexes to speed up certain calls
     *
     * @param  SchemaSetupInterface $setup
     * @param  ModuleContextInterface $context
     * @return void
     */
    protected function addIndexes(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.3.1', '<=')) {
            $connection = $setup->getConnection();

            $protocolApiTable = $setup->getTable($connection->getTableName(Api::TABLE_PROTOCOL_API));
            $connection->addIndex($protocolApiTable, $connection->getIndexName($protocolApiTable, 'txid'), 'txid');

            $transactionStatusTable = $setup->getTable($connection->getTableName(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS));
            $connection->addIndex($transactionStatusTable, $connection->getIndexName($transactionStatusTable, 'txid'), 'txid');
            $connection->addIndex($transactionStatusTable, $connection->getIndexName($transactionStatusTable, 'customerid'), 'customerid');
        }
    }

    /**
     * Upgrade method
     *
     * @param  SchemaSetupInterface $setup
     * @param  ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->addNewTables($setup, $context);
        $this->addNewColumns($setup, $context);
        $this->modifyColumns($setup, $context);
        $this->addIndexes($setup, $context);
    }
}
