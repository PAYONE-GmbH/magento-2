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
     * Constructor
     *
     * @param Session      $checkoutSession
     * @param OrderFactory $orderFactory
     * @param QuoteRepo    $quoteRepository
     */
    public function __construct(Session $checkoutSession, OrderFactory $orderFactory, QuoteRepo $quoteRepository)
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @return void
     */
    public function handle()
    {
        if ($this->checkoutSession->getPayoneCustomerIsRedirected()) {
            try {
                $orderId = $this->checkoutSession->getLastOrderId();
                $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
                if ($order) {
                    $order->cancel();
                    $order->addStatusHistoryComment(__('The Payone transaction has been canceled.'), Order::STATE_CANCELED);
                    $order->save();

                    $oCurrentQuote = $this->checkoutSession->getQuote();

                    $quoteId = $this->checkoutSession->getLastQuoteId();
                    $oOldQuote = $this->quoteRepository->get($quoteId);
                    if ($oOldQuote && $oOldQuote->getId()) {
                        $oCurrentQuote->merge($oOldQuote);
                        $oCurrentQuote->collectTotals();
                        $oCurrentQuote->save();
                    }

                    $this->checkoutSession
                        ->unsLastQuoteId()
                        ->unsLastSuccessQuoteId()
                        ->unsLastOrderId()
                        ->unsLastRealOrderId();

                    $this->checkoutSession->setPayoneCanceledOrder($order->getIncrementId());
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