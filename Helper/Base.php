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
     * Scope config object
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param  \Magento\Framework\App\Helper\Context              $context
     * @param  \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
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
        if (!$sStoreCode) {
            $sStoreCode = $this->storeManager->getStore()->getCode();
        }
        return $this->scopeConfig->getValue(
            $sSection."/".$sGroup."/".$sKey,
            ScopeInterface::SCOPE_STORE,
            $sStoreCode
        );
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
}
