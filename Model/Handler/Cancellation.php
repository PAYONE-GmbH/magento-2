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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Handler;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\LocalizedException;
use \Magento\Quote\Api\CartRepositoryInterface as QuoteRepo;
use Payone\Core\Model\ResourceModel\TransactionStatus;

class Cancellation
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Order factory
     *
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * Order repository
     *
     * @var QuoteRepo
     */
    protected $quoteRepository;

    /**
     * TransactionStatus resource model
     *
     * @var TransactionStatus
     */
    protected $transactionStatus;

    /**
     * Constructor
     *
     * @param Session           $checkoutSession
     * @param OrderFactory      $orderFactory
     * @param QuoteRepo         $quoteRepository
     * @param TransactionStatus $transactionStatus
     */
    public function __construct(Session $checkoutSession, OrderFactory $orderFactory, QuoteRepo $quoteRepository, TransactionStatus $transactionStatus)
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->transactionStatus = $transactionStatus;
    }

    /**
     * Determines if order should be canceled
     *
     * @param  Order $order
     * @return bool
     */
    protected function canCancelOrder(Order $order)
    {
        if ($this->hasReceivedAppointedStatus($order) === true) {
            return false;
        }

        if ($order->hasInvoices() === true) {
            return false;
        }

        return true;
    }

    /**
     * Checks if an appointed status was sent for this order
     *
     * @param  Order $order
     * @return bool
     */
    protected function hasReceivedAppointedStatus(Order $order)
    {
        $AppointedId = $this->transactionStatus->getAppointedIdByTxid($order->getPayoneTxid());
        if (!empty($AppointedId)) {
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    public function handle()
    {
        if ($this->checkoutSession->getPayoneCustomerIsRedirected()) {
            try {
                $orderId = $this->checkoutSession->getLastOrderId();
                /** @var Order $order */
                $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
                if ($order) {
                    if ($this->canCancelOrder($order)) {
                        $order->cancel();
                        $order
                            ->addStatusToHistory(Order::STATE_CANCELED, __('The Payone transaction has been canceled.'), false);
                        $order->save();

                        $oCurrentQuote = $this->checkoutSession->getQuote();

                        $quoteId = $this->checkoutSession->getLastQuoteId();
                        $oOldQuote = $this->quoteRepository->get($quoteId);
                        if ($oOldQuote && $oOldQuote->getId()) {
                            $oCurrentQuote->merge($oOldQuote);
                            $oCurrentQuote->collectTotals();
                            $oCurrentQuote->save();
                        }
                    }

                    $this->checkoutSession
                        ->unsLastQuoteId()
                        ->unsLastSuccessQuoteId()
                        ->unsLastOrderId()
                        ->unsLastRealOrderId();
                }
            } catch (LocalizedException $e) {
                // catch and continue - do something when needed
            } catch (\Exception $e) {
                // catch and continue - do something when needed
            }

            $this->checkoutSession->unsPayoneCustomerIsRedirected();
            $this->checkoutSession->setIsPayoneRedirectCancellation(true);
        }
    }
}
