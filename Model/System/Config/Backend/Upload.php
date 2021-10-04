<?php

namespace Payone\Core\Model\System\Config\Backend;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;

class Upload extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $_tmpDirectory;

    /**
     * @var \Payone\Core\Helper\ApplePay
     */
    protected $applePayHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Payone\Core\Helper\ApplePay $applePayHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Filesystem $filesystem,
        \Payone\Core\Helper\ApplePay $applePayHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->_tmpDirectory = $filesystem->getDirectoryRead(DirectoryList::SYS_TMP);
        $this->applePayHelper = $applePayHelper;
    }

    /**
     * Process additional data before save config
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (!empty($value['value'])) {
            $this->setValue($value['value']);
        } elseif (!isset($value['value'])) {
            $this->setValue('');
        }

        if (is_array($value) && !empty($value['delete'])) {
            $sCurrentFile = $this->applePayHelper->getApplePayUploadPath().$value['value'];
            if (file_exists($sCurrentFile)) {
                unlink($sCurrentFile);
            }
            $this->setValue('');
            return $this;
        }

        if (empty($value['tmp_name'])) {
            return $this;
        }

        $tmpPath = $this->_tmpDirectory->getRelativePath($value['tmp_name']);
        if ($tmpPath && $this->_tmpDirectory->isExist($tmpPath)) {
            if (!$this->_tmpDirectory->stat($tmpPath)['size']) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The PayPal certificate file is empty.'));
            }
            $this->setValue($value['name']);

            $this->moveFile($value['tmp_name'], $value['name']);
        }
        return $this;
    }

    /**
     * Copies file to upload path
     *
     * @param  string $sFilePath
     * @param  string $sFileName
     * @return void
     */
    protected function moveFile($sFilePath, $sFileName)
    {
        $sUploadPath = $this->applePayHelper->getApplePayUploadPath();
        if (!file_exists($sUploadPath)) {
            mkdir($sUploadPath);
        }
        move_uploaded_file($sFilePath, $sUploadPath.$sFileName);
    }
}
