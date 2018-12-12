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

namespace Payone\Core\Controller\Onepage;

use Magento\Sales\Model\Order;

/**
 * Controller for handling return from payment provider
 */
class Returned extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Quote management object
     *
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * Order repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Order repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

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
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Checkout\Model\Session                      $checkoutSession
     * @param \Magento\Quote\Model\QuoteManagement                 $quoteManagement
     * @param \Magento\Sales\Api\OrderRepositoryInterface          $orderRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface           $quoteRepository
     * @param \Payone\Core\Helper\Database                         $databaseHelper
     * @param \Payone\Core\Model\Entities\TransactionStatusFactory $statusFactory
     * @param \Payone\Core\Model\Handler\TransactionStatus         $transactionStatusHandler
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Payone\Core\Helper\Database $databaseHelper,
        \Payone\Core\Model\Entities\TransactionStatusFactory $statusFactory,
        \Payone\Core\Model\Handler\TransactionStatus $transactionStatusHandler
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->databaseHelper = $databaseHelper;
        $this->quoteRepository = $quoteRepository;
        $this->statusFactory = $statusFactory;
        $this->transactionStatusHandler = $transactionStatusHandler;
    }

    /**
     * Order was canceled before because of multi-tab browsing or back-button cancelling
     * Create a clean order for the payment
     *
     * @param  Order $canceledOrder
     * @return void
     */
    protected function createSubstituteOrder(Order $canceledOrder)
    {
        $this->checkoutSession->setPayoneCreatingSubstituteOrder(true);

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

        $this->checkoutSession->setLastOrderId($newOrder->getId());
        $this->checkoutSession->setLastRealOrderId($newOrder->getIncrementId());
        $this->checkoutSession->getQuote()->setIsActive(false)->save();

        $this->databaseHelper->relabelTransaction($canceledOrder->getId(), $newOrder->getId(), $newOrder->getPayment()->getId());
        $this->databaseHelper->relabelApiProtocol($canceledOrder->getIncrementId(), $newOrder->getIncrementId());
        $this->databaseHelper->relabelOrderPayment($canceledOrder->getIncrementId(), $newOrder->getId());

        $this->handleTransactionStatus($canceledOrder, $newOrder);

        $this->checkoutSession->unsPayoneCreatingSubstituteOrder();
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

    /**
     * Get canceled order.
     * Return order if found.
     * Return false if not found or not canceled
     *
     * @return bool|Order
     */
    protected function getCanceledOrder()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order->getId() && !empty($this->checkoutSession->getPayoneCanceledOrder())) {
            $order->loadByIncrementId($this->checkoutSession->getPayoneCanceledOrder());
        }
        $this->checkoutSession->unsPayoneCanceledOrder();
        if ($order->getStatus() == Order::STATE_CANCELED) {
            return $order;
        }
        return false;
    }

    /**
     * Redirect to success page
     * Do whatever processing is needed after successful return from payment-provider
     *
     * @return void
     */
    public function execute()
    {
        $this->checkoutSession->unsPayoneCustomerIsRedirected();

        $canceledOrder = $this->getCanceledOrder();
        if ($canceledOrder !== false) {
            $this->createSubstituteOrder($canceledOrder);
        }

        $this->_redirect($this->_url->getUrl('checkout/onepage/success'));
    }
}
