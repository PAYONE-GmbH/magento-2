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

namespace Payone\Core\Model\Export;

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
class Xml
{
    /**
     * String where the config export xml is written into
     *
     * @var string
     */
    protected $sXmlContent = '';

    /**
     * Tab content
     *
     * @var string
     */
    protected $sTab = "    ";

    /**
     * Line end sign
     *
     * @var string
     */
    protected $sLineEnd = "\n";

    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop $shopHelper
     */
    public function __construct(\Payone\Core\Helper\Shop $shopHelper)
    {
        $this->shopHelper = $shopHelper;
    }

    /**
     * Return xml content property
     *
     * @return string
     */
    protected function getXmlContent()
    {
        return rtrim($this->sXmlContent, $this->sLineEnd);
    }

    /**
     * Add content to the xml content property
     *
     * @param  string $sContent
     * @param  int    $iTabCount
     * @return void
     */
    protected function writeToXml($sContent, $iTabCount = 0)
    {
        for ($i = 0; $i < $iTabCount; $i++) {
            $sContent = $this->sTab.$sContent;
        }
        $this->sXmlContent .= $sContent.$this->sLineEnd;
    }

    /**
     * Write xml node to xml
     *
     * @param  string $sNode
     * @param  string $sContent
     * @param  int    $iTabCount
     * @return void
     */
    protected function writeNode($sNode, $sContent, $iTabCount = 0)
    {
        $this->writeToXml("<{$sNode}>$sContent</{$sNode}>", $iTabCount);
    }

    /**
     * Helper method to get parameter from the config
     * divided by the config path elements
     *
     * @param  string $sNode
     * @param  int    $iTabCount
     * @param  string $sStoreCode
     * @param  string $sKey
     * @param  string $sGroup
     * @param  string $sSection
     * @return void
     */
    protected function writeConfigNode($sNode, $iTabCount, $sStoreCode, $sKey, $sGroup = 'global', $sSection = 'payone_general')
    {
        $sContent = $this->shopHelper->getConfigParam($sKey, $sGroup, $sSection, $sStoreCode);
        $this->writeNode($sNode, $sContent, $iTabCount);
    }
}
