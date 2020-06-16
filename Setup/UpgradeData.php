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

use Magento\Customer\Setup\CustomerSetup;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Payone\Core\Helper\Shop;
use Magento\Customer\Setup\CustomerSetupFactory;
use Payone\Core\Helper\Payment;
use Magento\Store\Model\ScopeInterface;
use Payone\Core\Model\Source\CreditcardTypes;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * Config writer resource
     *
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * Store manager object
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * PAYONE shop helper object
     *
     * @var Shop
     */
    protected $shopHelper;

    /**
     * Eav setup factory
     *
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * PAYONE payment helper object
     *
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * Constructor
     *
     * @param SalesSetupFactory     $salesSetupFactory
     * @param Shop                  $shopHelper
     * @param Payment               $paymentHelper
     * @param WriterInterface       $configWriter
     * @param StoreManagerInterface $storeManager
     * @param CustomerSetupFactory  $customerSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        Shop $shopHelper,
        Payment $paymentHelper,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->shopHelper = $shopHelper;
        $this->paymentHelper = $paymentHelper;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * Upgrade method
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  ModuleContextInterface   $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_reference')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_reference',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_workorder_id')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_workorder_id',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_installment_duration')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_installment_duration',
                ['type' => 'integer', 'length' => null]
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankaccountholder')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankaccountholder',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankcountry')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankcountry',
                ['type' => 'varchar', 'length' => 2, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankaccount')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankaccount',
                ['type' => 'varchar', 'length' => 32, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankcode')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankcode',
                ['type' => 'varchar', 'length' => 32, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankiban')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankiban',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankbic')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankbic',
                ['type' => 'varchar', 'length' => 32, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankcity')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankcity',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_clearing_bankname')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_clearing_bankname',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_refund_iban')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_refund_iban',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_refund_bic')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_refund_bic',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_cancel_substitute_increment_id')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_cancel_substitute_increment_id',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }

        $serializedRows = $this->getSerializedConfigRows($setup);
        if (!empty($serializedRows) && version_compare($this->shopHelper->getMagentoVersion(), '2.2.0', '>=')) {
            $this->convertSerializedDataToJson($setup, $serializedRows);
        }

        if (version_compare($context->getVersion(), '2.2.0', '<=')) {// pre update version is less than or equal to 2.2.1
            $this->convertPersonstatusMappingConfig($setup);
        }

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_installment_duration')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_installment_duration',
                ['type' => 'integer', 'length' => null]
            );
        }

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'payone_express_type')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'payone_express_type',
                ['type' => 'varchar', 'length' => 64, 'default' => '']
            );
        }

        $customerInstaller = $this->customerSetupFactory->create(['setup' => $setup]);
        if ($setup->getConnection()->tableColumnExists($setup->getTable('customer_entity'), 'payone_paydirekt_registered')) {
            $customerInstaller->removeAttribute('customer', 'payone_paydirekt_registered');
            if (!$customerInstaller->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'payone_paydirekt_registered', 'attribute_id')) {
                $this->addPaydirektRegisteredAttribute($customerInstaller);
                $this->copyPaydirektRegisteredData($setup, $customerInstaller->getAttributeId('customer', 'payone_paydirekt_registered'));
                $setup->getConnection()->dropColumn($setup->getTable('customer_entity'), 'payone_paydirekt_registered');
            }
        } elseif (!$customerInstaller->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'payone_paydirekt_registered', 'attribute_id')) {
            $this->addPaydirektRegisteredAttribute($customerInstaller);
        }

        $this->deactivateNewPaymentMethods($setup);

        if (version_compare($context->getVersion(), '2.8.0', '<=')) { // pre update version is less than or equal to 2.8.0
            $this->convertCreditcardTypesConfig($setup);
        }

        $setup->endSetup();
    }

    /**
     * Adds payone_paydirekt_registered attribute
     *
     * @param  CustomerSetup $customerInstaller
     * @return void
     */
    protected function addPaydirektRegisteredAttribute(CustomerSetup $customerInstaller)
    {
        $customerInstaller->addAttribute(
            'customer',
            'payone_paydirekt_registered',
            [
                'type'         => 'int',
                'label'        => 'Payone paydirekt OneClick is registered',
                'input'        => 'text',
                'required'     => false,
                'visible'      => false,
                'user_defined' => false,
                'sort_order'   => 999,
                'position'     => 999,
                'system'       => 0,
            ]
        );
    }

    /**
     * Fetch all serialized config rows from the payone module from the DB
     *
     * @param  ModuleDataSetupInterface $setup
     * @return array
     */
    protected function getSerializedConfigRows(ModuleDataSetupInterface $setup)
    {
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('core_config_data'), ['config_id', 'value'])
            ->where('path LIKE "%payone%"')
            ->where('value LIKE "a:%"');

        return $setup->getConnection()->fetchAssoc($select);
    }

    /**
     * Convert serialized data to json-encoded data in the core_config_data table
     *
     * @param ModuleDataSetupInterface $setup
     * @param array                    $serializedRows
     * @return void
     */
    protected function convertSerializedDataToJson(ModuleDataSetupInterface $setup, $serializedRows)
    {
        foreach ($serializedRows as $id => $serializedRow) {
            $aValue = unserialize($serializedRow['value']);
            $sNewValue = json_encode($aValue);

            $data = ['value' => $sNewValue];
            $where = ['config_id = ?' => $id];
            $setup->getConnection()->update($setup->getTable('core_config_data'), $data, $where);
        }
    }

    /**
     * Change config path of personstatus mapping configuration
     *
     * @param  ModuleDataSetupInterface $setup
     * @return void
     */
    protected function convertPersonstatusMappingConfig(ModuleDataSetupInterface $setup)
    {
        $data = ['path' => 'payone_protect/personstatus/mapping'];
        $where = ['path = ?' => 'payone_protect/address_check/mapping_personstatus'];
        $setup->getConnection()->update($setup->getTable('core_config_data'), $data, $where);
    }

    /**
     * Updates configured creditcard types from old data handling to new data handling
     *
     * @param  ModuleDataSetupInterface $setup
     * @return void
     */
    protected function convertCreditcardTypesConfig(ModuleDataSetupInterface $setup)
    {
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('core_config_data'), ['config_id', 'value'])
            ->where('path = "payone_payment/payone_creditcard/types"');
        $result = $setup->getConnection()->fetchAssoc($select);

        $cardTypes = CreditcardTypes::getCreditcardTypes();
        foreach ($result as $row) {
            $newCardTypes = [];
            $activatedCardtypes = explode(',', $row['value']);
            foreach ($activatedCardtypes as $activeCardType) {
                if ($activeCardType == "C") {
                    $newCardTypes[] = "discover";
                } elseif ($activeCardType == "D") {
                    $newCardTypes[] = "dinersclub";
                } else {
                    foreach ($cardTypes as $cardTypeId => $cardType) {
                        if ($cardType['cardtype'] == $activeCardType) {
                            $newCardTypes[] = $cardTypeId;
                        }
                    }
                }
            }

            $data = ['value' => implode(",", $newCardTypes)];
            $where = ['config_id = ?' => $row['config_id']];
            $setup->getConnection()->update($setup->getTable('core_config_data'), $data, $where);
        }
    }

    /**
     * Adds a config entry to the database to set the payment method to inactive
     *
     * @param  string $methodCode
     * @return void
     */
    protected function addPaymentInactiveConfig($methodCode)
    {
        $this->configWriter->save('payment/'.$methodCode.'/active', 0);
    }

    /**
     * Checks if there is a active config entry for the given payment method
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  string                   $methodCode
     * @return bool
     */
    protected function isPaymentConfigExisting(ModuleDataSetupInterface $setup, $methodCode)
    {
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('core_config_data'), ['config_id', 'value'])
            ->where('path LIKE "%'.$methodCode.'/active"');

        $result = $setup->getConnection()->fetchAssoc($select);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /**
     * Copied payone_paydirekt_registered data from the customer_entity table to the eav_entities
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  int                      $attributeId
     * @return void
     */
    protected function copyPaydirektRegisteredData(ModuleDataSetupInterface $setup, $attributeId)
    {
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('customer_entity'), ['entity_id'])
            ->where('payone_paydirekt_registered = 1');
        $result = $setup->getConnection()->fetchAssoc($select);

        foreach ($result as $row) {
            $setup->getConnection()->insert(
                $setup->getTable('customer_entity_int'),
                [
                    'attribute_id' => $attributeId,
                    'entity_id' => $row['entity_id'],
                    'value' => 1,
                ]
            );
        }
    }

    /**
     * Deactivates new payment methods, since they have to be marked as active in the config.xml files to be shown in payment method select backend elements
     *
     * @param  ModuleDataSetupInterface $setup
     * @return void
     */
    protected function deactivateNewPaymentMethods(ModuleDataSetupInterface $setup)
    {
        foreach ($this->paymentHelper->getAvailablePaymentTypes() as $methodCode) {
            if ($this->isPaymentConfigExisting($setup, $methodCode) === false) {
                $this->addPaymentInactiveConfig($methodCode);
            }
        }
    }
}
