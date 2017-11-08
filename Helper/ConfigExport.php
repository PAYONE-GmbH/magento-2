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

namespace Payone\Core\Helper;

/**
 * Helper class for the config export
 */
class ConfigExport extends \Payone\Core\Helper\Base
{
    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * PAYONE config helper
     *
     * @var \Payone\Core\Helper\Config
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Payone\Core\Helper\Payment                $paymentHelper
     * @param \Payone\Core\Helper\Database               $databaseHelper
     * @param \Payone\Core\Helper\Config                 $configHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Payment $paymentHelper,
        \Payone\Core\Helper\Database $databaseHelper,
        \Payone\Core\Helper\Config $configHelper
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->paymentHelper = $paymentHelper;
        $this->databaseHelper = $databaseHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Format module info from db
     *
     * @return array
     */
    public function getModuleInfo()
    {
        $aModules = [];

        $aResult = $this->databaseHelper->getModuleInfo();
        if ($aResult) {
            foreach ($aResult as $aRow) {
                $aModules[$aRow['module']] = $aRow['schema_version'];
            }
        }
        return $aModules;
    }

    /**
     * Get the configured status mappings for all payment types
     * for the given store
     *
     * @param  string $sStoreCode
     * @return array
     */
    public function getMappings($sStoreCode)
    {
        $aMappings = [];

        $aPaymentTypes = $this->paymentHelper->getAvailablePaymentTypes();
        foreach ($aPaymentTypes as $sPaymentCode) {
            $sPaymentMapping = $this->getConfigParam($sPaymentCode, 'statusmapping', 'payone_general', $sStoreCode);
            $aPaymentMapping = false;
            if (!empty($sPaymentMapping)) {
                $aPaymentMapping = $this->unserialize($sPaymentMapping);
            }
            if (is_array($aPaymentMapping) && !empty($aPaymentMapping)) {
                $aMap = [];
                foreach ($aPaymentMapping as $aPayMap) {
                    $aMap[] = [
                        'from' => $aPayMap['txaction'],
                        'to' => $aPayMap['state_status'],
                    ];
                }
                $aMappings[$this->paymentHelper->getPaymentAbbreviation($sPaymentCode)] = $aMap;
            }
        }
        return $aMappings;
    }

    /**
     * Get all configured status forwardings for the given store
     *
     * @param  string $sStoreCode
     * @return array
     */
    public function getForwardings($sStoreCode)
    {
        $aForwardingReturn = [];
        $aForwarding = $this->configHelper->getForwardingUrls($sStoreCode);
        foreach ($aForwarding as $aForwardEntry) {
            if (isset($aForwardEntry['txaction']) && !empty($aForwardEntry['txaction'])) {
                $aForwardingReturn[] = [
                    'status' => implode(',', $aForwardEntry['txaction']),
                    'url' => $aForwardEntry['url'],
                    'timeout' => (int)$aForwardEntry['timeout'],
                ];
            }
        }
        return $aForwardingReturn;
    }

    /**
     * Get config parameters of certain payment-types
     *
     * @param  string $sParam
     * @param  string $sPaymentCode
     * @param  string $sStoreCode
     * @param  bool   $blCheckGlobal
     * @return string
     */
    public function getPaymentConfig($sParam, $sPaymentCode, $sStoreCode, $blCheckGlobal = false)
    {
        $iPaymentUseGlobal = $this->getConfigParam('use_global', $sPaymentCode, 'payone_payment', $sStoreCode);
        if (!$blCheckGlobal || ($blCheckGlobal && $iPaymentUseGlobal == '0')) {
            return $this->getConfigParam($sParam, $sPaymentCode, 'payone_payment', $sStoreCode);
        }
        return $this->getConfigParam($sParam, 'global', 'payone_general', $sStoreCode);
    }

    /**
     * Get the allowed countries for a given payment type
     * or an empty string if all countries are allowed
     *
     * @param  string $sPaymentCode
     * @param  string $sStoreCode
     * @return string
     */
    public function getCountries($sPaymentCode, $sStoreCode)
    {
        if ($this->getPaymentConfig('allowspecific', $sPaymentCode, $sStoreCode, true) == '1') {
            return $this->getPaymentConfig('specificcountry', $sPaymentCode, $sStoreCode, true);
        }
        return ''; // empty return value if all countries are available
    }
}
