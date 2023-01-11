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
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;

/**
 * Class for adding columns to the order grid table
 */
class InstallData implements \Magento\Framework\Setup\InstallDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * Constructor
     *
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * Constructor
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  ModuleContextInterface   $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
        $salesInstaller->addAttribute(
            'order',
            'payone_txid',
            ['type' => 'varchar', 'length' => 64, 'default' => '', 'grid' => true]
        );
        $salesInstaller->addAttribute(
            'order',
            'payone_refnr',
            ['type' => 'varchar', 'length' => 32, 'default' => '', 'grid' => true]
        );
        $salesInstaller->addAttribute(
            'order',
            'payone_transaction_status',
            ['type' => 'varchar', 'length' => 32, 'default' => '', 'grid' => true]
        );
        $salesInstaller->addAttribute(
            'order',
            'payone_authmode',
            ['type' => 'varchar', 'length' => 32, 'default' => '', 'grid' => true]
        );
        $salesInstaller->addAttribute(
            'order',
            'payone_mode',
            ['type' => 'varchar', 'length' => 8, 'default' => '']
        );
        $salesInstaller->addAttribute(
            'order',
            'payone_mandate_id',
            ['type' => 'varchar', 'length' => 64, 'default' => '']
        );

        $this->preconfigureGermanPaymentTitles($setup);

        $setup->endSetup();
    }

    /**
     * Add German payment title for stores with German locale
     *
     * @param  ModuleDataSetupInterface $setup
     * @return void
     */
    protected function preconfigureGermanPaymentTitles(ModuleDataSetupInterface $setup)
    {
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('core_config_data'), ['scope', 'scope_id'])
            ->where('path = "general/locale/code"')
            ->where('value LIKE "de_%"')
            ->order(['scope_id', 'scope']);
        $result = $setup->getConnection()->fetchAssoc($select);

        foreach ($result as $item) {
            $this->addGermanPaymentTitles($setup, $item['scope'], $item['scope_id']);
        }
    }

    /**
     * Inserts new config rows with German payment titles in core_config_data table
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  string $scope
     * @param  string $scopeId
     * @return void
     */
    protected function addGermanPaymentTitles(ModuleDataSetupInterface $setup, $scope, $scopeId)
    {
        $translations = [
            'payone_cash_on_delivery' => 'PAYONE Nachnahme',
            'payone_creditcard' => 'PAYONE Kreditkarte',
            'payone_debit' => 'PAYONE Lastschrift',
            'payone_advance_payment' => 'PAYONE Vorkasse',
            'payone_invoice' => 'PAYONE Rechnung',
            'payone_safe_invoice' => 'PAYONE Gesicherter Rechnungskauf',
            'payone_bnpl_invoice' => 'PAYONE Rechnungskauf',
            'payone_bnpl_debit' => 'PAYONE Lastschrift in 14 Tagen',
            'payone_bnpl_installment' => 'PAYONE Ratenkauf',
        ];

        foreach ($translations as $paymentId => $translation) {
            $setup->getConnection()->insert(
                $setup->getTable('core_config_data'),
                [
                    'scope' => $scope,
                    'scope_id' => $scopeId,
                    'path' => 'payment/'.$paymentId.'/title',
                    'value' => $translation,
                ]
            );
        }
    }
}
