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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Handler;

use Magento\Sales\Model\Order;

class SubstituteOrder
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Order repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Order repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Quote management object
     *
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * Registry object
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * TransactionStatus factory
     *
     * @var \Payone\Core\Model\Entities\TransactionStatusFactory
     */
    protected $statusFactory;

    /**
     * TransactionStatus handler
     *
     * @var \Payone\Core\Model\Handler\TransactionStatus
     */
    protected $transactionStatusHandler;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session                      $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface           $quoteRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface          $orderRepository
     * @param \Magento\Quote\Model\QuoteManagement                 $quoteManagement
     * @param \Payone\Core\Helper\Database                         $databaseHelper
     * @param \Magento\Framework\Registry                          $registry
     * @param \Payone\Core\Model\Entities\TransactionStatusFactory $statusFactory
     * @param \Payone\Core\Model\Handler\TransactionStatus         $transactionStatusHandler
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Payone\Core\Helper\Database $databaseHelper,
        \Magento\Framework\Registry $registry,
        \Payone\Core\Model\Entities\TransactionStatusFactory $statusFactory,
        \Payone\Core\Model\Handler\TransactionStatus $transactionStatusHandler
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->quoteManagement = $quoteManagement;
        $this->databaseHelper = $databaseHelper;
        $this->registry = $registry;
        $this->statusFactory = $statusFactory;
        $this->transactionStatusHandler = $transactionStatusHandler;
    }

    /**
     * Updates the checkout session to the new order
     *
     * @param  Order $oOrder
     * @return void
     */
    public function updateCheckoutSession(Order $oOrder)
    {
        $this->checkoutSession->setLastOrderId($oOrder->getId());
        $this->checkoutSession->setLastRealOrderId($oOrder->getIncrementId());
        $this->checkoutSession->setLastSuccessQuoteId($oOrder->getQuoteId());
        $this->checkoutSession->setLastQuoteId($oOrder->getQuoteId());
        $this->checkoutSession->getQuote()->setIsActive(false)->save();
    }

    /**
     * Order was canceled before because of multi-tab browsing or back-button cancelling
     * Create a clean order for the payment
     *
     * @param  Order $canceledOrder
     * @param  bool  $blUpdateSession
     * @return Order
     */
    public function createSubstituteOrder(Order $canceledOrder, $blUpdateSession = true)
    {
        $this->registry->register('payone_creating_substitute_order', true, true);

        $oOldQuote = $this->quoteRepository->get($canceledOrder->getQuoteId());
        $oOldQuote->setIsActive(true);
        $oOldQuote->setReservedOrderId(null);
        $oOldQuote->save();

        $orderId = $this->quoteManagement->placeOrder($oOldQuote->getId());
        $newOrder = $this->orderRepository->get($orderId);

        $oldData = $canceledOrder->getData();
        foreach ($oldData as $sKey => $sValue) {
            if (stripos($sKey, 'payone') !== false) {
                $newOrder->setData($sKey, $sValue);
            }
        }

        $newOrder->setPayoneCancelSubstituteIncrementId($canceledOrder->getIncrementId());
        $newOrder->save();

        if ($blUpdateSession === true) {
            $this->updateCheckoutSession($newOrder);
        }

        $this->databaseHelper->relabelTransaction($canceledOrder->getId(), $newOrder->getId(), $newOrder->getPayment()->getId());
        $this->databaseHelper->relabelApiProtocol($canceledOrder->getIncrementId(), $newOrder->getIncrementId());
        $this->databaseHelper->relabelOrderPayment($canceledOrder->getIncrementId(), $newOrder->getId());

        $this->handleTransactionStatus($canceledOrder, $newOrder);

        $this->registry->unregister('payone_creating_substitute_order');

        return $newOrder;
    }

    /**
     * Handle stored TransactionStatus
     *
     * @param  Order $canceledOrder
     * @param  Order $newOrder
     * @return void
     */
    protected function handleTransactionStatus(Order $canceledOrder, Order $newOrder)
    {
        $aTransactionStatusIds = $this->databaseHelper->getNotHandledTransactionsByOrderId($canceledOrder->getIncrementId());
        foreach ($aTransactionStatusIds as $aRow) {
            $oTransactionStatus = $this->statusFactory->create();
            $oTransactionStatus->load($aRow['id']);

            $this->transactionStatusHandler->handle($newOrder, $oTransactionStatus->getRawStatusArray());

            $oTransactionStatus->setHasBeenHandled(true);
            $oTransactionStatus->save();
        }
    }
}