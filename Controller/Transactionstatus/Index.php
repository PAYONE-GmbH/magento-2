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

namespace Payone\Core\Controller\Transactionstatus;

use Magento\Sales\Model\Order;
use Payone\Core\Model\Methods\PayoneMethod;

/**
 * TransactionStatus receiver
 */
class Index extends \Payone\Core\Controller\ExternalAction
{
    /**
     * TransactionStatus model
     *
     * @var \Payone\Core\Model\ResourceModel\TransactionStatus
     */
    protected $transactionStatus;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * PAYONE environment helper
     *
     * @var \Payone\Core\Helper\Environment
     */
    protected $environmentHelper;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Order
     */
    protected $orderHelper;

    /**
     * TransactionStatus handler
     *
     * @var \Payone\Core\Model\Handler\TransactionStatus
     */
    protected $transactionStatusHandler;

    /**
     * Result factory for file-download
     *
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * PAYONE substitute order handler
     *
     * @var \Payone\Core\Model\Handler\SubstituteOrder
     */
    protected $substituteOrder;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Framework\Data\Form\FormKey               $formKey
     * @param \Payone\Core\Model\ResourceModel\TransactionStatus $transactionStatus
     * @param \Payone\Core\Helper\Toolkit                        $toolkitHelper
     * @param \Payone\Core\Helper\Environment                    $environmentHelper
     * @param \Payone\Core\Helper\Order                          $orderHelper
     * @param \Payone\Core\Model\Handler\TransactionStatus       $transactionStatusHandler,
     * @param \Magento\Framework\Controller\Result\RawFactory    $resultRawFactory
     * @param \Payone\Core\Model\Handler\SubstituteOrder         $substituteOrder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Payone\Core\Model\ResourceModel\TransactionStatus $transactionStatus,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Order $orderHelper,
        \Payone\Core\Model\Handler\TransactionStatus $transactionStatusHandler,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Payone\Core\Model\Handler\SubstituteOrder $substituteOrder
    ) {
        parent::__construct($context, $formKey);
        $this->transactionStatus = $transactionStatus;
        $this->toolkitHelper = $toolkitHelper;
        $this->environmentHelper = $environmentHelper;
        $this->orderHelper = $orderHelper;
        $this->transactionStatusHandler = $transactionStatusHandler;
        $this->resultRawFactory = $resultRawFactory;
        $this->substituteOrder = $substituteOrder;
    }

    /**
     * Return request parameter value
     *
     * @param  string $sKey
     * @return string
     */
    protected function getParam($sKey)
    {
        return $this->getRequest()->getParam($sKey, '');
    }

    /**
     * Return Post array
     *
     * @return array
     */
    protected function getPostArray()
    {
        return $this->getRequest()->getPostValue();
    }

    /**
     * Write the TransactionStatus to the database
     *
     * @param  Order $oOrder
     * @param  array $aRequest
     * @param  bool  $blWillBeHandled
     * @return void
     */
    protected function logTransactionStatus(Order $oOrder, $aRequest, $blWillBeHandled)
    {
        $this->transactionStatus->addTransactionLogEntry($aRequest, $oOrder, $blWillBeHandled);
    }

    /**
     * Main method for executing all needed actions for the incoming TransactionStatus
     *
     * @return string
     */
    protected function handleTransactionStatus()
    {
        if (!$this->environmentHelper->isRemoteIpValid()) {
            return 'Access denied';
        } elseif (!$this->toolkitHelper->isKeyValid($this->getParam('key'))) {
            return 'Key wrong or missing!';
        }

        $oOrder = $this->orderHelper->getOrderByTxid($this->getParam('txid'));
        if (!$oOrder) {
            return 'Order not found';
        }

        if ($this->getParam('txaction') == 'appointed' && $oOrder->getStatus() == 'canceled') {
            // order was canceled in checkout, probably due to browser-back-button usage -> create a new order for incoming payment
            $oOrder = $this->substituteOrder->createSubstituteOrder($oOrder, false);
        }

        $this->logTransactionStatus($oOrder, $this->getPostArray(), true);

        if (!empty($oOrder->getPayment()) &&
            !empty($oOrder->getPayment()->getMethodInstance()) &&
            $oOrder->getPayment()->getMethodInstance() instanceof PayoneMethod &&
            $oOrder->getPayment()->getMethodInstance()->canHandleTransactionStatus($this->getPostArray()) === true
        ) { // There are special cases with certain payment methods where transaction status shall be ignored - so canHandleTransactionStatus === false -> ignore transaction status
            $this->transactionStatusHandler->handle($oOrder, $this->getPostArray());
        }

        return 'TSOK';
    }

    /**
     * Executing TransactionStatus handling
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $sOutput = $this->handleTransactionStatus();

        $oResultRaw = $this->resultRawFactory->create();
        $oResultRaw->setContents($sOutput);
        
        return $oResultRaw;
    }
}
