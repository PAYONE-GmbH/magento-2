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

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Class for installing all PAYONE specific tables
 */
class InstallSchema extends BaseSchema implements InstallSchemaInterface
{
    /**
     * Install method
     *
     * @param  SchemaSetupInterface   $installer
     * @param  ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $installer, ModuleContextInterface $context)
    {
        $installer->startSetup();

        $aTablesToAdd = $this->getTablesToAdd();
        foreach ($aTablesToAdd as $aTableData) {
            $this->addTable($installer, $aTableData);
        }

        $installer->endSetup();
    }

    /**
     * Return the data arrays for the tables to add
     *
     * @return array
     */
    protected function getTablesToAdd()
    {
        return [
            \Payone\Core\Setup\Tables\Api::getData(),
            \Payone\Core\Setup\Tables\Transactionstatus::getData()
        ];
    }
}
