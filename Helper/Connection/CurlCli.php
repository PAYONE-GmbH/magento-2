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
 * Helper class for connections with cli curl
 */
class CurlCli
{
    /**
     * Send cli Curl request
     *
     * @param  array $aParsedRequestUrl
     * @return array
     */
    public function sendCurlCliRequest($aParsedRequestUrl)
    {
        $sCurlPath = file_exists("/usr/local/bin/curl") ? "/usr/local/bin/curl" : "/usr/bin/curl";

        $aResponse = [];

        $sPostUrl = $aParsedRequestUrl['scheme']."://".$aParsedRequestUrl['host'].$aParsedRequestUrl['path'];
        $sPostData = $aParsedRequestUrl['query'];

        $sCommand = $sCurlPath." -m 45 -k -d \"".$sPostData."\" ".$sPostUrl;

        $iSysOut = -1;
        exec($sCommand, $aResponse, $iSysOut);
        if ($iSysOut != 0) {
            $aResponse[] = "connection-type: 2 - errormessage=curl error(".$iSysOut.")";
        }

        return $aResponse;
    }
}
