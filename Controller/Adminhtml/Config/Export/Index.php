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

namespace Payone\Core\Controller\Adminhtml\Config\Export;

use Magento\Backend\App\Action;

/**
 * Controller for configuration-export
 */
class Index extends Action
{
    /**
     * Object that creates the export xml
     *
     * @var \Payone\Core\Model\Config\Export
     */
    protected $configExport;

    /**
     * Result factory for file-download
     *
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Constructor
     *
     * @param  \Magento\Backend\App\Action\Context             $context
     * @param  \Payone\Core\Model\Config\Export                $configExport
     * @param  \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Payone\Core\Model\Config\Export $configExport,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->configExport = $configExport;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Return if the user has the needed rights to view this page
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Payone_Core::payone_configuration_export');
    }

    /**
     * File download-output for configuration export xml file
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $oResultRaw = $this->resultRawFactory->create();
        try {
            $sXml = $this->configExport->generateConfigExportXml();
            if ($sXml !== false) {
                $oResultRaw->setHeader("Content-Type", "text/xml; charset=\"utf8\"", true);
                $oResultRaw->setHeader("Content-Disposition", "attachment; filename=\"payone_config_export".date('Y-m-d H-i-s')."_".md5($sXml).".xml\"", true);
                $oResultRaw->setContents($sXml);
            }
        } catch (\Exception $e) {
            $oResultRaw->setContents($e->getMessage());
        }
        return $oResultRaw;
    }
}
