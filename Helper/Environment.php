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
 * Helper class for everything that has to do with the server environment
 */
class Environment extends \Payone\Core\Helper\Base
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context        $context
     * @param \Magento\Store\Model\StoreManagerInterface   $storeManager
     * @param \Payone\Core\Helper\Shop                     $shopHelper
     * @param \Magento\Framework\App\State                 $state
     * @param \Magento\Framework\App\RequestInterface      $request
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\RequestInterface $request
    ) {
        parent::__construct($context, $storeManager, $shopHelper, $state);
        $this->request = $request;
    }

    /**
     * Get encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return 'UTF-8';
    }

    /**
     * Get the IP of the requesting client
     *
     * @return string
     */
    public function getRemoteIp()
    {
        $blProxyMode = (bool)$this->getConfigParam('proxy_mode', 'processing', 'payone_misc');
        $sClientIp = $this->request->getClientIp($blProxyMode); // may return a comma separated ip list like "<client>, <proxy1>, <proxy2>"
        $aSplitIp = explode(",", $sClientIp); // split by comma
        return trim(current($aSplitIp)); // return first array element
    }

    /**
     * Validate if the user-ip-address is in the configured whitelist
     *
     * @return bool
     */
    public function isRemoteIpValid()
    {
        $sRemoteIp = $this->getRemoteIp();
        $sValidIps = $this->getConfigParam('valid_ips', 'processing', 'payone_misc');
        $aWhitelist = explode("\n", $sValidIps ?? '');
        $aWhitelist = array_filter(array_map('trim', $aWhitelist));
        if (array_search($sRemoteIp, $aWhitelist) !== false) {
            return true;
        }
        foreach ($aWhitelist as $sIP) {
            if (stripos($sIP, '*') !== false) {
                $sIP = str_replace(array("\r", "\n"), '', $sIP);
                $sDelimiter = '/';

                $sRegex = preg_quote($sIP, $sDelimiter);
                $sRegex = str_replace('\*', '\d{1,3}', $sRegex);
                $sRegex = $sDelimiter.'^'.$sRegex.'$'.$sDelimiter;
                
                preg_match($sRegex, $sRemoteIp, $aMatches);
                if (is_array($aMatches) && !empty($aMatches) && $aMatches[0] == $sRemoteIp) {
                    return true;
                }
            }
        }
        return false;
    }
}
