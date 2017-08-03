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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Controller\Adminhtml\Config\Amazon;

use Magento\Backend\App\Action;

/**
 * Controller for Amazon get configuration script
 */
class Configuration extends Action
{
    /**
     * Result factory for get configuration script
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Object of getconfiguration request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration
     */
    protected $getConfiguration;

    /**
     * Amazon Pay payment object
     *
     * @var \Payone\Core\Model\Methods\AmazonPay
     */
    protected $payment;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context                            $context
     * @param \Magento\Framework\Controller\Result\JsonFactory               $resultJsonFactory
     * @param \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration $getConfiguration
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration $getConfiguration,
        \Payone\Core\Model\Methods\AmazonPay $payment
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->getConfiguration = $getConfiguration;
        $this->payment = $payment;
    }

    /**
     * Return if the user has the needed rights to view this page
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Payone_Core::payone_configuration_payment');
    }

    /**
     * Return amazon configuration data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $aData = false;

        $aResult = $this->getConfiguration->sendRequest($this->payment);
        if (isset($aResult['status'])) {
            if ($aResult['status'] == 'OK') {
                $aData['success'] = true;
                $aData['client_id'] = $aResult['add_paydata[client_id]'];
                $aData['seller_id'] = $aResult['add_paydata[seller_id]'];
            } elseif ($aResult['status'] == 'ERROR') {
                $aData['success'] = false;
                $aData['errormessage'] = $aResult['errormessage'];
            }
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($aData);
    }
}
