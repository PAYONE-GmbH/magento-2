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
        $sClientIp = null;
        $sForwardFor = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING);
        if (!empty($sForwardFor)) {
            $aIps = explode(',', $sForwardFor);
            $sClientIp = trim($aIps[0]);
        }
        $sReportAddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING);
        $sRemoteIp = isset($sClientIp) ? $sClientIp : $sReportAddr;
        return $sRemoteIp;
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
        $aWhitelist = explode("\n", $sValidIps);
        if (array_search($sRemoteIp, $aWhitelist) !== false) {
            return true;
        }
        foreach ($aWhitelist as $sIP) {
            if (stripos($sIP, '*') !== false) {
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
