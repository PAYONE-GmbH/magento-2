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

namespace Payone\Core\Model\Config;

use Magento\Store\Api\Data\StoreInterface;

/**
 * Generator class for the config export
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
class Export extends \Payone\Core\Model\Export\Xml
{
    /**
     * ConfigExport helper object
     *
     * @var \Payone\Core\Helper\ConfigExport
     */
    protected $configExportHelper;

    /**
     * ChecksumCheck model
     *
     * @var \Payone\Core\Model\ChecksumCheck
     */
    protected $checksumCheck;

    /**
     * Store manage object
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * PAYONE addresscheck model
     *
     * @var \Payone\Core\Model\Risk\Addresscheck
     */
    protected $addresscheck;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\ConfigExport           $configExportHelper
     * @param \Payone\Core\Model\ChecksumCheck           $checksumCheck
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Payment                $paymentHelper
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Payone\Core\Model\Risk\Addresscheck       $addresscheck
     */
    public function __construct(
        \Payone\Core\Helper\ConfigExport $configExportHelper,
        \Payone\Core\Model\ChecksumCheck $checksumCheck,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Payment $paymentHelper,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Model\Risk\Addresscheck $addresscheck
    ) {
        parent::__construct($shopHelper);
        $this->configExportHelper = $configExportHelper;
        $this->checksumCheck = $checksumCheck;
        $this->storeManager = $storeManager;
        $this->paymentHelper = $paymentHelper;
        $this->addresscheck = $addresscheck;
    }

    /**
     * Add shop system config to xml
     *
     * @return void
     */
    protected function addShopSystemConfig()
    {
        $this->writeToXml('<system>', 2);
        $this->writeNode('name', 'Magento2', 3);
        $this->writeNode('version', $this->shopHelper->getMagentoVersion(), 3);
        $this->writeNode('edition', $this->shopHelper->getMagentoEdition(), 3);
        $this->writeToXml('<modules>', 3);
        foreach ($this->configExportHelper->getModuleInfo() as $sModule => $sInfo) {
            $this->writeNode($sModule, $sInfo, 4);
        }
        $this->writeToXml('</modules>', 3);
        $this->writeToXml('</system>', 2);
    }

    /**
     * Add shop global config to xml
     *
     * @param  string $sStoreCode
     * @return void
     */
    protected function addStatusMappings($sStoreCode)
    {
        $this->writeToXml('<status_mapping>', 3);
        foreach ($this->configExportHelper->getMappings($sStoreCode) as $sAbbr => $aMappings) {
            $this->writeToXml("<{$sAbbr}>", 4);
            foreach ($aMappings as $aMap) {
                $this->writeToXml('<map from="'.$aMap['from'].'" to="'.$aMap['to'].'"/>', 4);
            }
            $this->writeToXml("</{$sAbbr}>", 4);
        }
        $this->writeToXml('</status_mapping>', 3);
    }

    /**
     * Add shop global config to xml
     *
     * @param  string $sStoreCode
     * @return void
     */
    protected function addShopGlobalConfig($sStoreCode)
    {
        $this->writeToXml('<global>', 2);
        $this->writeConfigNode('mid', 3, $sStoreCode, 'mid');
        $this->writeConfigNode('aid', 3, $sStoreCode, 'aid');
        $this->writeConfigNode('portalid', 3, $sStoreCode, 'portalid');
        $this->writeConfigNode('refnr_prefix', 3, $sStoreCode, 'ref_prefix');
        $this->writeConfigNode('request_type', 3, $sStoreCode, 'request_type');
        $this->writeConfigNode('pdf_download_enabled', 3, $sStoreCode, 'pdf_download_enabled', 'invoicing');
        $this->writeConfigNode('transmit_enabled', 3, $sStoreCode, 'transmit_enabled', 'invoicing');
        $this->writeToXml('<parameter_invoice>', 3);
        $this->writeToXml("<invoice_appendix><![CDATA[{$this->configExportHelper->getConfigParam('invoice_appendix', 'invoicing', 'payone_general', $sStoreCode)}]]></invoice_appendix>", 4);
        $this->writeToXml("<invoice_appendix_refund><![CDATA[{$this->configExportHelper->getConfigParam('invoice_appendix_refund', 'invoicing', 'payone_general', $sStoreCode)}]]></invoice_appendix_refund>", 3);
        $this->writeToXml('</parameter_invoice>', 3);
        $this->addStatusMappings($sStoreCode);
        $this->writeToXml('</global>', 2);
    }

    /**
     * Add shop clearingtype config to xml
     *
     * @param  string $sStoreCode
     * @return void
     */
    protected function addShopClearingtypeConfig($sStoreCode)
    {
        $this->writeToXml('<clearingtypes>', 2);
        foreach ($this->paymentHelper->getAvailablePaymentTypes() as $sPaymentCode) {
            $sAbbr = $this->paymentHelper->getPaymentAbbreviation($sPaymentCode);
            $this->writeToXml("<{$sAbbr}>", 3);
            $this->writeToXml("<title><![CDATA[{$this->configExportHelper->getConfigParam('title', $sPaymentCode, 'payment', $sStoreCode)}]]></title>", 4);
            $this->writeNode("id", $sPaymentCode, 4);
            $this->writeNode("mid", $this->configExportHelper->getPaymentConfig('mid', $sPaymentCode, $sStoreCode, true), 4);
            $this->writeNode("aid", $this->configExportHelper->getPaymentConfig('aid', $sPaymentCode, $sStoreCode, true), 4);
            $this->writeNode("portalid", $this->configExportHelper->getPaymentConfig('portalid', $sPaymentCode, $sStoreCode, true), 4);
            $this->writeNode("refnr_prefix", $this->configExportHelper->getPaymentConfig('ref_prefix', $sPaymentCode, $sStoreCode, true), 4);
            $this->writeConfigNode('min_order_total', 4, $sStoreCode, 'min_order_total', $sPaymentCode, 'payment');
            $this->writeConfigNode('max_order_total', 4, $sStoreCode, 'max_order_total', $sPaymentCode, 'payment');
            $this->writeConfigNode('active', 4, $sStoreCode, 'active', $sPaymentCode, 'payment');
            $this->writeNode("countries", $this->configExportHelper->getCountries($sPaymentCode, $sStoreCode), 4);
            $this->writeNode("authorization", $this->configExportHelper->getPaymentConfig('request_type', $sPaymentCode, $sStoreCode, true), 4);
            $this->writeNode("mode", $this->configExportHelper->getPaymentConfig('mode', $sPaymentCode, $sStoreCode), 4);
            $this->writeToXml("</{$sAbbr}>", 3);
        }
        $this->writeToXml('</clearingtypes>', 2);
    }

    /**
     * Add addresscheck config to xml
     *
     * @param  string $sStoreCode
     * @return void
     */
    protected function addAddresscheckConfig($sStoreCode)
    {
        $this->writeToXml('<addresscheck>', 3);
        $this->writeNode("active", $this->configExportHelper->getConfigParam('enabled', 'address_check', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("mode", $this->configExportHelper->getConfigParam('mode', 'address_check', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("min_order_total", $this->configExportHelper->getConfigParam('min_order_total', 'address_check', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("max_order_total", $this->configExportHelper->getConfigParam('max_order_total', 'address_check', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("checkbilling", $this->configExportHelper->getConfigParam('check_billing', 'address_check', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("checkshipping", $this->configExportHelper->getConfigParam('check_shipping', 'address_check', 'payone_protect', $sStoreCode), 4);
        $this->writeToXml('<personstatusmapping>', 4);
        $aMapping = $this->addresscheck->getPersonstatusMapping();
        foreach ($aMapping as $sPersonstatus => $sScore) {
            $this->writeNode($sPersonstatus, $sScore, 5);
        }
        $this->writeToXml('</personstatusmapping>', 4);
        $this->writeToXml('</addresscheck>', 3);
    }

    /**
     * Add consumerscore config to xml
     *
     * @param  string $sStoreCode
     * @return void
     */
    protected function addConsumerscore($sStoreCode)
    {
        $this->writeToXml('<consumerscore>', 3);
        $this->writeNode("active", $this->configExportHelper->getConfigParam('enabled', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("mode", $this->configExportHelper->getConfigParam('mode', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("min_order_total", $this->configExportHelper->getConfigParam('min_order_total', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("max_order_total", $this->configExportHelper->getConfigParam('max_order_total', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("consumerscoretype", $this->configExportHelper->getConfigParam('type', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("red", $this->configExportHelper->getConfigParam('allow_payment_methods_red', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("yellow", $this->configExportHelper->getConfigParam('allow_payment_methods_yellow', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeNode("duetime", $this->configExportHelper->getConfigParam('result_lifetime', 'creditrating', 'payone_protect', $sStoreCode), 4);
        $this->writeToXml('</consumerscore>', 3);
    }

    /**
     * Add shop protect config to xml
     *
     * @param  string $sStoreCode
     * @return void
     */
    protected function addProtectConfig($sStoreCode)
    {
        $this->writeToXml('<protect>', 2);
        $this->addAddresscheckConfig($sStoreCode);
        $this->addConsumerscore($sStoreCode);
        $this->writeToXml('</protect>', 2);
    }

    /**
     * Add shop misc config to xml
     *
     * @param  string $sStoreCode
     * @return void
     */
    protected function addShopMiscConfig($sStoreCode)
    {
        $this->writeToXml('<misc>', 2);
        $this->writeToXml('<transactionstatus_forwarding>', 3);
        foreach ($this->configExportHelper->getForwardings($sStoreCode) as $aForward) {
            $this->writeToXml('<config status="'.$aForward['status'].'" url="'.htmlentities($aForward['url']).'" timeout="'.$aForward['timeout'].'"/>', 4);
        }
        $this->writeToXml('</transactionstatus_forwarding>', 3);
        $this->writeToXml('<shipping_costs>', 3);
        $this->writeNode("sku", $this->configExportHelper->getConfigParam('sku', 'costs', 'payone_misc', $sStoreCode), 4);
        $this->writeToXml('</shipping_costs>', 3);
        $this->writeToXml('</misc>', 2);
    }

    /**
     * Write single shop config to xml
     *
     * @param  string         $sStoreCode
     * @param  StoreInterface $oStore
     * @return void
     */
    protected function addSingleShopConfig($sStoreCode, StoreInterface $oStore)
    {
        $this->writeToXml('<shop>', 1);
        $this->writeNode("code", $sStoreCode, 2);
        $this->writeToXml("<name><![CDATA[{$oStore->getName()}]]></name>", 2);
        $this->addShopSystemConfig();
        $this->addShopGlobalConfig($sStoreCode);
        $this->addShopClearingtypeConfig($sStoreCode);
        $this->addProtectConfig($sStoreCode);
        $this->addShopMiscConfig($sStoreCode);
        $this->writeToXml('</shop>', 1);
    }

    /**
     * Write checksum status to the xml
     *
     * @return void
     */
    protected function addChecksums()
    {
        $aErrors = $this->checksumCheck->getChecksumErrors();
        if ($aErrors === false) {
            $this->writeNode("status", "Correct", 2);
        } elseif (is_array($aErrors) && !empty($aErrors)) {
            $this->writeNode("status", "Error", 2);
            $this->writeToXml('<errors>', 2);
            foreach ($aErrors as $sError) {
                $this->writeNode("error", base64_encode($sError), 3);
            }
            $this->writeToXml('</errors>', 2);
        }
    }

    /**
     * Add status info to xml
     *
     * @return void
     */
    protected function addStatus()
    {
        $this->writeToXml('<checksums>', 1);
        if (ini_get('allow_url_fopen') == 0) {
            $this->writeNode("status", "Cant verify checksums, allow_url_fopen is not activated on customer-server", 2);
        } elseif (!function_exists('curl_init')) {
            $this->writeNode("status", "Cant verify checksums, curl is not activated on customer-server", 2);
        } else {
            $this->addChecksums();
        }
        $this->writeToXml('</checksums>', 1);
    }

    /**
     * Get all stores and write the config xml entries for each shop to the xml
     *
     * @return void
     */
    protected function addShopConfigs()
    {
        $aShopIds = $this->storeManager->getStores(false, true);
        foreach ($aShopIds as $sStoreCode => $oStore) {
            $this->addSingleShopConfig($sStoreCode, $oStore);
        }
    }

    /**
     * Generates the content of the configuration export xml
     *
     * @return string
     */
    public function generateConfigExportXml()
    {
        $this->writeToXml('<?xml version="1.0" encoding="UTF-8"?>');
        $this->writeToXml('<config>');
        $this->addShopConfigs();
        $this->addStatus();
        $this->writeToXml('</config>');
        return $this->getXmlContent();
    }
}
