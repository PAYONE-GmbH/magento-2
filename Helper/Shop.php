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

use Locale;

/**
 * Helper class for everything that has to do with the magento shop
 */
class Shop extends \Payone\Core\Helper\Base
{
    /**
     * Product metadata object
     *
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ProductMetadata     $productMetadata Magentos metadata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        parent::__construct($context, $storeManager, $this);
        $this->productMetadata = $productMetadata;
    }

    /**
     * Returns the shop-version of the Magento 2 shop
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Returns the magento edition
     *
     * @return string
     */
    public function getMagentoEdition()
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * Retrieve label store scope
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Return locale config of current store
     *
     * @return string
     */
    public function getLocale()
    {
        return Locale::getPrimaryLanguage($this->getConfigParam('code', 'locale', 'general'));
    }
}
