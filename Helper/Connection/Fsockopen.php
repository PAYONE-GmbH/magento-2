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
 * Helper class for connections with fsockopen
 */
class Fsockopen
{
    /**
     * Determine if this connection type can be used on the given server
     *
     * @return bool
     */
    public function isApplicable()
    {
        if (function_exists("fsockopen")) {
            return true;
        }
        return false;
    }

    /**
     * Get request header for fsockopen request
     *
     * @param  array $aParsedRequestUrl
     * @return string
     */
    protected function getSocketRequestHeader($aParsedRequestUrl)
    {
        $sRequestHeader  = "POST ".$aParsedRequestUrl['path']." HTTP/1.1\r\n";
        $sRequestHeader .= "Host: ".$aParsedRequestUrl['host']."\r\n";
        $sRequestHeader .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $sRequestHeader .= "Content-Length: ".strlen($aParsedRequestUrl['query'])."\r\n";
        $sRequestHeader .= "Connection: close\r\n\r\n";
        $sRequestHeader .= $aParsedRequestUrl['query'];
        return $sRequestHeader;
    }

    /**
     * Read the response from fsockopen request
     *
     * @param  object $oFsockOpen
     * @return array
     */
    protected function getSocketResponse($oFsockOpen)
    {
        $aResponse = [];

        $sResponseHeader = "";
        do {
            $sResponseHeader .= fread($oFsockOpen, 1);
        } while (!preg_match("/\\r\\n\\r\\n$/", $sResponseHeader) && !feof($oFsockOpen));

        while (!feof($oFsockOpen)) {
            $aResponse[] = fgets($oFsockOpen, 1024);
        }
        if (count($aResponse) == 0) {
            $aResponse[] = 'connection-type: 3 - '.$sResponseHeader;
        }
        return $aResponse;
    }

    /**
     * Send fsockopen request
     *
     * @param  array $aParsedRequestUrl
     * @return array
     */
    public function sendSocketRequest($aParsedRequestUrl)
    {
        if (!$this->isApplicable()) {
            return ["errormessage" => "Cli-Curl is not applicable on this server."];
        }

        $iErrorNumber = '';
        $sErrorString = '';

        $sScheme = '';
        $iPort = 80;
        if ($aParsedRequestUrl['scheme'] == 'https') {
            $sScheme = 'ssl://';
            $iPort = 443;
        }

        $oFsockOpen = fsockopen($sScheme.$aParsedRequestUrl['host'], $iPort, $iErrorNumber, $sErrorString, 45);
        if ($oFsockOpen) {
            fwrite($oFsockOpen, $this->getSocketRequestHeader($aParsedRequestUrl));
            return $this->getSocketResponse($oFsockOpen);
        }
        return ["errormessage=fsockopen:Failed opening http socket connection: ".$sErrorString." (".$iErrorNumber.")"];
    }
}
