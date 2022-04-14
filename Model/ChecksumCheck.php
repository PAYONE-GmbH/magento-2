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

namespace Payone\Core\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * Checksum check
 */
class ChecksumCheck
{
    /**
     * module id
     *
     * @var string
     */
    protected $sModuleId = null;

    /**
     * module name
     *
     * @var string
     */
    protected $sModuleName = null;

    /**
     * module version
     *
     * @var string
     */
    protected $sModuleVersion = null;

    /**
     * Returnes if composer.json was found
     *
     * @var bool
     */
    protected $blGotModuleInfo = null;

    /**
     * URL to Fatchip checksum check
     *
     * @var string
     */
    protected $sVersionCheckUrl = 'http://version.fatchip.de/fcVerifyChecksum.php';

    /**
     * Get module base path
     *
     * @return string
     */
    protected function getBasePath()
    {
        return dirname(__FILE__).'/../';
    }

    /**
     * Read module info from composer.json
     *
     * @param  string $sFilePath
     * @return void
     */
    protected function handleComposerJson($sFilePath)
    {
        $sFile = file_get_contents($sFilePath);
        if (!empty($sFile)) {// was file readable?
            $aFile = json_decode($sFile, true);
            if (isset($aFile['name'])) {// is name property set in composer.json?
                $this->sModuleId = preg_replace('#[^A-Za-z0-9]#', '_', $aFile['name']);
                $this->sModuleName = $aFile['name'];
            }
            if (isset($aFile['version'])) {// is version property set in composer.json?
                $this->sModuleVersion = $aFile['version'];
            }
            $this->blGotModuleInfo = true;
        }
    }

    /**
     * Read module version from module.xml
     *
     * @param  string $sFilePath
     * @return void
     */
    protected function handleModuleXml($sFilePath) {
        $oXml = simplexml_load_file($sFilePath);
        if ($oXml && $oXml->module) {
            $sVersion = $oXml->module->attributes()->setup_version;
            if ($sVersion) {
                $this->sModuleVersion = $sVersion;
            }
            $this->blGotModuleInfo = true;
        }
    }

    /**
     * Request files existing in the module from Fatchip checksum server
     *
     * @return array
     */
    protected function getFilesToCheck()
    {
        $aFiles = [];
        if (file_exists($this->getBasePath().'composer.json')) {// does composer.json exist here?
            $this->handleComposerJson($this->getBasePath().'composer.json'); // Read module information from the composer.json
        }
        if (file_exists($this->getBasePath()."/etc/module.xml")) {// does module.xml exist here?
            $this->handleModuleXml($this->getBasePath()."/etc/module.xml"); // Read module information from the module.xml
        }
        if ($this->blGotModuleInfo === true) { // was composer.json readable?
            $sRequestUrl = $this->sVersionCheckUrl.'?module='.$this->sModuleId.'&version='.$this->sModuleVersion;
            $sResponse = file_get_contents($sRequestUrl); // request info from fatchip checksum server
            if (!empty($sResponse)) {// Did the server answer?
                $aFiles = json_decode($sResponse); // Decode the json encoded answer from the server
            }
        }
        return $aFiles;
    }

    /**
     * Collect the md5 checksums for all the files given as content
     * of this module
     *
     * @param  array $aFiles
     * @return array
     */
    protected function checkFiles($aFiles)
    {
        $aChecksums = [];
        foreach ($aFiles as $sFilePath) {
            $sFullFilePath = $this->getBasePath().$sFilePath;
            if (file_exists($sFullFilePath)) {
                $aChecksums[md5($sFilePath)] = md5_file($sFullFilePath); // Create md5 checksum of the file
            }
        }
        return $aChecksums;
    }

    /**
     * Send collected checksums to Fatchip checksum server and receive
     * if all files are unchanged
     *
     * @param  array $aChecksums
     * @return string
     */
    protected function getCheckResults($aChecksums)
    {
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $this->sVersionCheckUrl);
        curl_setopt($oCurl, CURLOPT_HEADER, false);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, [
            'checkdata' => json_encode($aChecksums), // checksums of all module files
            'module' => $this->sModuleId, // module identification
            'version' => $this->sModuleVersion, // current module version
        ]);
        $sResult = curl_exec($oCurl);
        curl_close($oCurl);
        return $sResult;
    }

    /**
     * Main method executing checksum check
     *
     * @return string
     * @throws LocalizedException
     */
    public function checkChecksumXml()
    {
        if (ini_get('allow_url_fopen') == 0) {// Is file_get_contents for urls active on this server?
            throw new LocalizedException(__("Cant verify checksums, allow_url_fopen is not activated on customer-server!"));
        } elseif (!function_exists('curl_init')) {// is curl usable on this server?
            throw new LocalizedException(__("Cant verify checksums, curl is not activated on customer-server!"));
        }

        $aFiles = $this->getFilesToCheck(); // Requests all files that need to be checked from the Fatchip Checksum Server
        $aChecksums = $this->checkFiles($aFiles); // Collect checksums of all files that need to be checked
        return $this->getCheckResults($aChecksums); // Send the checksums to the Fatchip Checksum Server and have them checked
    }

    /**
     * Execute the checksum check and return false for a correct check
     * or an array with all errors
     *
     * @return array|bool
     */
    public function getChecksumErrors()
    {
        $sResult = $this->checkChecksumXml();
        if ($sResult == 'correct') {// were all checksums correct?
            return false;
        }

        $aErrors = json_decode(stripslashes($sResult) ?? '', true);
        if ($aErrors === null) {// fallback for when stripslashes is not needed
            $aErrors = json_decode($sResult ?? '', true);
        }
        if (is_array($aErrors)) {
            return $aErrors;
        }
    }
}
