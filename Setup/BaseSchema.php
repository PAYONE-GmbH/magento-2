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

use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Base class for installing or updating all PAYONE specific tables
 */
class BaseSchema
{
    /**
     * This method will add a new table into the shop database
     *
     * @param  SchemaSetupInterface $installer
     * @param  array                $aTableData
     * @return void
     */
    protected function addTable(SchemaSetupInterface $installer, $aTableData)
    {
        $oConnection = $installer->getConnection();
        $sRealTableName = $installer->getTable($aTableData['title']);
        if (!$oConnection->isTableExists($sRealTableName)) {
            $table = $oConnection->newTable($sRealTableName);

            foreach ($aTableData['columns'] as $sColumnName => $aColumnData) {
                $table->addColumn($sColumnName, $aColumnData['type'], $aColumnData['length'], $aColumnData['option']);
            }

            if (!empty($aTableData['indexes'])) {
                foreach ($aTableData['indexes'] as $sIndex) {
                    $table->addIndex($installer->getIdxName($aTableData['title'], $sIndex), $sIndex);
                }
            }

            $table->setComment($aTableData['comment']);

            $oConnection->createTable($table);
        }
    }
}
