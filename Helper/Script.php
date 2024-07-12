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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Helper;

use Magento\Framework\App\ObjectManager;

class Script extends \Payone\Core\Helper\Base
{
    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        parent::__construct($context, $storeManager, $shopHelper, $state);
        $this->productMetadata = $productMetadata;
    }

    /**
     * Magento 2.4.7 doesnt allow inline javascript - this method is used to handle this
     *
     * @param  string $script
     * @return string
     */
    public function insertScript($script)
    {
        $sReturn = "<script>".$script."</script>";
        if (version_compare($this->productMetadata->getVersion(), '2.4.7', '>=')) {
            $secureRenderer = ObjectManager::getInstance()->create(\Magento\Framework\View\Helper\SecureHtmlRenderer::class);
            $sReturn = $secureRenderer->renderTag('script', [], $script, false);
        }
        return $sReturn;
    }

    /**
     * Magento 2.4.7 doesnt allow inline javascript - this method is used to handle this
     *
     * @return string
     */
    public function insertEvent($sEventName, $sScript)
    {
        $sReturn = $sEventName."='".$sScript."'";
        if (false && version_compare($this->productMetadata->getVersion(), '2.4.7', '>=')) {
            $secureRenderer = ObjectManager::getInstance()->create(\Magento\Framework\View\Helper\SecureHtmlRenderer::class);
            $sReturn = $secureRenderer->renderEventListener($sEventName, $sScript);
        }
        return $sReturn;

    }
}
