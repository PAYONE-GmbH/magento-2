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

namespace Payone\Core\Controller\Mandate;

use Magento\Sales\Model\Order;

/**
 * Controller for mandate download
 */
class Download extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Payone getfile request model
     *
     * @var \Payone\Core\Model\Api\Request\Getfile
     */
    protected $getfileRequest;

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Result factory for file-download
     *
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Order object
     *
     * @var Order
     */
    protected $oOrder = null;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Magento\Checkout\Model\Session                 $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Getfile          $getfileRequest
     * @param \Payone\Core\Helper\Payment                     $paymentHelper
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Getfile $getfileRequest,
        \Payone\Core\Helper\Payment $paymentHelper,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->getfileRequest = $getfileRequest;
        $this->paymentHelper = $paymentHelper;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Get order object
     *
     * @return Order
     */
    protected function getOrder()
    {
        if ($this->oOrder === null) {
            $this->oOrder = $this->checkoutSession->getLastRealOrder();
        }
        return $this->oOrder;
    }

    /**
     * Get pdf-file string
     *
     * @return string
     */
    protected function getMandate()
    {
        $oOrder = $this->getOrder();
        $oPayment = $oOrder->getPayment()->getMethodInstance();
        $sMandate = $this->getfileRequest->sendRequest($oOrder, $oPayment);
        return $sMandate;
    }

    /**
     * Output mandate as pdf download
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $oResultRaw = $this->resultRawFactory->create();
        $sContent = 'Error';
        if ($this->paymentHelper->isMandateManagementDownloadActive()) {
            $oOrder = $this->getOrder();
            $sContent = 'Error - order not found';
            if ($oOrder) {
                $sContent = $this->getMandate();
                $sFilename = $oOrder->getPayoneMandateId().'.pdf';

                $oResultRaw->setHeader("Content-Type", "application/pdf", true);
                $oResultRaw->setHeader("Content-Disposition", 'attachment; filename="'.$sFilename.'"', true);
            }
        }
        $oResultRaw->setContents($sContent);
        return $oResultRaw;
    }
}
