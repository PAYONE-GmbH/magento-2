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

/**
 * TransactionStatus receiver
 */
class Index extends \Magento\Framework\App\Action\Action
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
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Payone\Core\Model\ResourceModel\TransactionStatus $transactionStatus
     * @param \Payone\Core\Helper\Toolkit                        $toolkitHelper
     * @param \Payone\Core\Helper\Environment                    $environmentHelper
     * @param \Payone\Core\Helper\Order                          $orderHelper
     * @param \Payone\Core\Model\Handler\TransactionStatus       $transactionStatusHandler,
     * @param \Magento\Framework\Controller\Result\RawFactory    $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Payone\Core\Model\ResourceModel\TransactionStatus $transactionStatus,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Order $orderHelper,
        \Payone\Core\Model\Handler\TransactionStatus $transactionStatusHandler,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->transactionStatus = $transactionStatus;
        $this->toolkitHelper = $toolkitHelper;
        $this->environmentHelper = $environmentHelper;
        $this->orderHelper = $orderHelper;
        $this->transactionStatusHandler = $transactionStatusHandler;
        $this->resultRawFactory = $resultRawFactory;
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
    protected function logTransactionStatus(Order $oOrder = null, $aRequest, $blWillBeHandled)
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

        $blWillBeHandled = true;
        $oOrder = $this->orderHelper->getOrderByTxid($this->getParam('txid'));
        if ($this->getParam('txaction') == 'appointed' && $oOrder->getStatus() == 'canceled') {
            $blWillBeHandled = false; // order was already canceled, status will be handled in substitute order mechanism, if order is finished
        }

        $this->logTransactionStatus($oOrder, $this->getPostArray(), $blWillBeHandled);

        if ($blWillBeHandled === true) {
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
