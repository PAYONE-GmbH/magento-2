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
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Helper;

use Payone\Core\Model\PayoneConfig;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Helper class for sending emails
 */
class ApplePay extends \Payone\Core\Helper\Base
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Framework\Filesystem              $filesystem
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context, $storeManager, $shopHelper, $state);
        $this->storeManager = $storeManager;
        $this->shopHelper = $shopHelper;
        $this->state = $state;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns ApplePay file upload path
     *
     * @param  bool $blForceOldPath
     * @return string
     */
    public function getApplePayUploadPath($blForceOldPath = false)
    {
        $sNewPath = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath('payone-gmbh/ApplePay/');
        if($blForceOldPath === false && file_exists($sNewPath)) { // file upload path was refactored to work for Magento cloud too
            return $sNewPath;
        }
        return __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."ApplePay".DIRECTORY_SEPARATOR;
    }

    /**
     * Checks if all needed configuration fields are correctly configured
     *
     * @return bool
     */
    public function isConfigurationComplete()
    {
        if ($this->hasMerchantId() && $this->hasCertificateFile() && $this->hasPrivateKeyFile()) {
            return true;
        }
        return false;
    }

    /**
     * Check if merchant id configured
     *
     * @return bool
     */
    public function hasMerchantId()
    {
        if (!empty($this->getConfigParam("merchant_id", PayoneConfig::METHOD_APPLEPAY, "payment"))) {
            return true;
        }
        return false;
    }

    /**
     * Check if file exists in ApplePay upload directory
     *
     * @param  string $sFile
     * @return bool
     */
    protected function isFileExisting($sFile)
    {
        if (empty($sFile)) {
            return false;
        }

        if (file_exists($this->getApplePayUploadPath().$sFile) || file_exists($this->getApplePayUploadPath(true).$sFile)) {
            return true;
        }
        return false;
    }

    /**
     * Check if certificate file is configured and exists
     *
     * @return bool
     */
    public function hasCertificateFile()
    {
        $sCertificateFile = $this->getConfigParam("certificate_file", PayoneConfig::METHOD_APPLEPAY, "payment");
        if ($this->isFileExisting($sCertificateFile) === true) {
            return true;
        }
        return false;
    }

    /**
     * Check if private key file is configured and exists
     *
     * @return bool
     */
    public function hasPrivateKeyFile()
    {
        $sPrivateKeyFile = $this->getConfigParam("private_key_file", PayoneConfig::METHOD_APPLEPAY, "payment");
        if ($this->isFileExisting($sPrivateKeyFile) === true) {
            return true;
        }
        return false;
    }
}
