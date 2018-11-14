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
        if (version_compare($context->getVersion(), '1.3.0', '<')) {// pre update version is lower than 1.3.0
            $this->addTable($setup, \Payone\Core\Setup\Tables\CheckedAddresses::getData());

            $setup->getConnection('checkout')->addColumn(
                $setup->getTable('quote_address'),
                'payone_addresscheck_score',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 1,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'AddressCheck Person Status Score (G, Y, R)'
                ]
            );
            $setup->getConnection('checkout')->addColumn(
                $setup->getTable('quote_address'),
                'payone_protect_score',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 1,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Consumerscore Status Score (G, Y, R)'
                ]
            );
        }
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

            $protocolApiTable = $connection->getTableName(Api::TABLE_PROTOCOL_API);
            $connection->addIndex($protocolApiTable, $connection->getIndexName($protocolApiTable, 'txid'), 'txid');

            $transactionStatusTable = $connection->getTableName(Transactionstatus::TABLE_PROTOCOL_TRANSACTIONSTATUS);
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
        $this->addNewColumns($setup, $context);
        $this->addNewTables($setup, $context);
        $this->modifyColumns($setup, $context);
        $this->addIndexes($setup, $context);
    }
}
