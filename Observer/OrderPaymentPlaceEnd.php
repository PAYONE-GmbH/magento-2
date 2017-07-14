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

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Payone\Core\Helper\Consumerscore;
use Magento\Sales\Model\Order\Payment;

/**
 * Event class to set the orderstatus to new and pending
 */
class OrderPaymentPlaceEnd implements ObserverInterface
{
    /**
     * PAYONE payment helper
     *
     * @var Consumerscore
     */
    protected $consumerscoreHelper;

    /**
     * Constructor
     *
     * @param Consumerscore $consumerscoreHelper
     */
    public function __construct(Consumerscore $consumerscoreHelper)
    {
        $this->consumerscoreHelper = $consumerscoreHelper;
    }

    /**
     * Handle order status
     *
     * @param  Observer $observer
     * @return void
     */
    protected function handleOrderStatus(Observer $observer)
    {
        /** @var Payment $oPayment */
        $oPayment = $observer->getEvent()->getPayment();
        $oPaymentInstance = $oPayment->getMethodInstance();
        if (stripos($oPaymentInstance->getCode(), 'payone') !== false) {
            $oOrder = $oPayment->getOrder();
            $oOrder->setState(Order::STATE_NEW);
            $oOrder->setStatus($oPaymentInstance->getConfigData('order_status'));
        }
    }

    /**
     * Execute certain tasks after the payment is placed and thus the order is placed
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // set status to new - pending on new orders
        $this->handleOrderStatus($observer);

        // increment counter for every order, needed for the A/B test feature
        $this->consumerscoreHelper->incrementConsumerscoreSampleCounter();
    }
}
