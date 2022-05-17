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

namespace Payone\Core\Helper\Connection;

/**
 * Helper class for connections with php curl
 */
class CurlPhp
{
    /**
     * Determine if this connection type can be used on the given server
     *
     * @return bool
     */
    public function isApplicable()
    {
        if (function_exists("curl_init")) {
            return true;
        }
        return false;
    }

    /**
     * Send php Curl request
     *
     * @param  array $aParsedRequestUrl
     * @return array
     */
    public function sendCurlPhpRequest($aParsedRequestUrl)
    {
        if (!$this->isApplicable()) {
            return ["errormessage" => "Php-Curl is not applicable on this server."];
        }

        $aResponse = [];

        $oCurl = curl_init($aParsedRequestUrl['scheme']."://".$aParsedRequestUrl['host'].$aParsedRequestUrl['path']);
        curl_setopt($oCurl, CURLOPT_POST, 1);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $aParsedRequestUrl['query']);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 45);

        $sResult = curl_exec($oCurl);
        if (curl_error($oCurl)) {
            $aResponse[] = "connection-type: 1 - errormessage=".curl_errno($oCurl).": ".curl_error($oCurl);
        } elseif (!empty($sResult)) {
            $aResponse = explode("\n", $sResult);
        }
        curl_close($oCurl);

        return $aResponse;
    }
}
