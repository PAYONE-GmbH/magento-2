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

use Magento\Store\Model\ScopeInterface;

/**
 * Helper base class
 */
class Base extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Store manager object
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Framework\App\State               $state
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->shopHelper = $shopHelper;
        $this->state = $state;
    }

    /**
     * Helper method to get parameter from the config
     * divided by the config path elements
     *
     * @param  string $sKey
     * @param  string $sGroup
     * @param  string $sSection
     * @param  string $sStoreCode
     * @return string
     */
    public function getConfigParam($sKey, $sGroup = 'global', $sSection = 'payone_general', $sStoreCode = null)
    {
        $sPath = $sSection."/".$sGroup."/".$sKey;
        return $this->getConfigParamByPath($sPath, $sStoreCode);
    }

    /**
     * Helper method to get parameter from the config by path
     *
     * @param  string $sPath
     * @param  string $sStoreCode
     * @return string
     */
    public function getConfigParamByPath($sPath, $sStoreCode = null)
    {
        $sScopeCode = ScopeInterface::SCOPE_STORES;
        if (!$sStoreCode) {
            list($sStoreCode, $sScopeCode) = $this->fetchCurrentStoreCode();
        }
        return $this->scopeConfig->getValue($sPath, $sScopeCode, $sStoreCode);
    }

    /**
     * Trying to fetch the current storeCode
     * Fetching the correct storeCode in the Magento2 backend is very inaccurate
     *
     * @return array
     */
    protected function fetchCurrentStoreCode()
    {
        $sScopeCode = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        $sStoreCode = $this->storeManager->getStore()->getCode();
        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $sStoreCode = 0; // 0 = default config, which should be used when neither website nor store parameter are present, storeManager returns default STORE though, which would be wrong
            if (!empty($this->getRequestParameter('website'))) {
                $sStoreCode = $this->getRequestParameter('website');
                $sScopeCode = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
            }
            if (!empty($this->getRequestParameter('store'))) {
                $sStoreCode = $this->getRequestParameter('store');
            }
        }

        return [$sStoreCode, $sScopeCode];
    }

    /**
     * Get a certain config param for all available stores
     *
     * @param  string $sKey
     * @param  string $sGroup
     * @param  string $sSection
     * @return array
     */
    public function getConfigParamAllStores($sKey, $sGroup = 'global', $sSection = 'payone_general')
    {
        $aValues = [];
        $aShopIds = $this->storeManager->getStores(false, true);
        foreach ($aShopIds as $sStoreCode => $oStore) {
            $sValue = $this->getConfigParam($sKey, $sGroup, $sSection, $sStoreCode);
            if (array_search($sValue, $aValues) === false) {
                $aValues[] = $sValue;
            }
        }
        return $aValues;
    }

    /**
     * Get parameter from the request
     *
     * @param  string $sParameter
     * @return mixed
     */
    public function getRequestParameter($sParameter)
    {
        return $this->_getRequest()->getParam($sParameter);
    }

    /**
     * Checks if the given value is json encoded
     *
     * @param  $sValue
     * @return bool
     */
    protected function isJson($sValue)
    {
        if (is_string($sValue) && is_array(json_decode($sValue, true)) && (json_last_error() == JSON_ERROR_NONE)) {
            return true;
        }
        return false;
    }

    /**
     * Handle the serialization of strings depending on the Magento version
     *
     * @param  mixed $mValue
     * @return string
     */
    public function serialize($mValue)
    {
        if (version_compare($this->shopHelper->getMagentoVersion(), '2.2.0', '>=')) { // Magento 2.2.0 and above
            return json_encode($mValue);
        }
        return serialize($mValue);
    }

    /**
     * @param  string $sValue
     * @return mixed
     */
    public function unserialize($sValue)
    {
        if ($this->isJson($sValue)) {
            return json_decode($sValue, true);
        }
        return unserialize($sValue ?? '');
    }
}
