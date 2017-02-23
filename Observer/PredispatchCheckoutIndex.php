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

namespace Payone\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Event class to prevent the basket from getting lost with redirect payment types
 * when the customer uses the browser back-button
 */
class PredispatchCheckoutIndex implements ObserverInterface
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
     * Constructor
     *
     * @param Session      $checkoutSession
     * @param OrderFactory $orderFactory
     */
    public function __construct(Session $checkoutSession, OrderFactory $orderFactory)
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param  Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if ($this->checkoutSession->getPayoneCustomerIsRedirected()) {
            try {
                $orderId = $this->checkoutSession->getLastOrderId();
                $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
                if ($order) {
                    $order->cancel()->save();

                    $this->checkoutSession->restoreQuote();

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
