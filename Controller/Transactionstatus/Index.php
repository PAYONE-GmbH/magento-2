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
     * Contect object
     *
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

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
     * PAYONE TransactionStatus Mapping
     *
     * @var \Payone\Core\Model\TransactionStatus\Mapping
     */
    protected $statusMapping;

    /**
     * PAYONE TransactionStatus Forwarding
     *
     * @var \Payone\Core\Model\TransactionStatus\Forwarding
     */
    protected $statusForwarding;

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
     * @param \Payone\Core\Model\TransactionStatus\Mapping       $statusMapping
     * @param \Payone\Core\Model\TransactionStatus\Forwarding    $statusForwarding
     * @param \Magento\Framework\Controller\Result\RawFactory    $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Payone\Core\Model\ResourceModel\TransactionStatus $transactionStatus,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Order $orderHelper,
        \Payone\Core\Model\TransactionStatus\Mapping $statusMapping,
        \Payone\Core\Model\TransactionStatus\Forwarding $statusForwarding,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->transactionStatus = $transactionStatus;
        $this->toolkitHelper = $toolkitHelper;
        $this->environmentHelper = $environmentHelper;
        $this->orderHelper = $orderHelper;
        $this->statusMapping = $statusMapping;
        $this->statusForwarding = $statusForwarding;
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
        return $this->context->getRequest()->getParam($sKey, '');
    }

    /**
     * Return Post array
     *
     * @return array
     */
    protected function getPostArray()
    {
        return $this->context->getRequest()->getPost()->toArray();
    }

    /**
     * Write the TransactionStatus to the database
     *
     * @param  Order $oOrder
     * @return void
     */
    protected function log(Order $oOrder = null)
    {
        $this->transactionStatus->addTransactionLogEntry($this->context, $oOrder);
    }

    /**
     * Order processing
     *
     * @param  Order $oOrder
     * @return void
     */
    protected function handleOrder(Order $oOrder)
    {
        $sAction = $this->getParam('txaction');
        $oOrder->setPayoneTransactionStatus($sAction);
        $oOrder->save();
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
        }
        if ($this->toolkitHelper->isKeyValid($this->getParam('key'))) {
            $oOrder = $this->orderHelper->getOrderByTxid($this->getParam('txid'));
            $this->log($oOrder);
            if ($oOrder) {
                $this->handleOrder($oOrder);
                $this->statusMapping->handleMapping($oOrder, $this->getParam('txaction'));
            }
            $this->statusForwarding->handleForwardings($this->getPostArray());
            return 'TSOK';
        }
        return 'Key wrong or missing!';
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
