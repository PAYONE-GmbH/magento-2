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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Magento script for updating the database after the initial installation
 */
class UpgradeSchema extends BaseSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrade method
     *
     * @param  SchemaSetupInterface   $setup
     * @param  ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
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
    }
}
