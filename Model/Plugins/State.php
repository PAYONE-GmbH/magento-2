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

namespace Payone\Core\Model\Plugins;

use Magento\Sales\Model\ResourceModel\Order\Handler\State as OrigState;
use Magento\Sales\Model\Order;
use Payone\Core\Model\Methods\PayoneMethod;

class State
{
    /**
     * Payone shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper = null;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop $shopHelper
     */
    public function __construct(\Payone\Core\Helper\Shop $shopHelper) {
        $this->shopHelper = $shopHelper;
    }

    /**
     * Closed status bug exists since Magento 2.3.1
     * So if the used payment method is not a Payone method or the shop-version is 2.3.0 or lower, the original method can be used
     * Version-condition must be completed with an endversion when the problem is solved in Mage2 core!
     *
     * Plugin method will only be used for virtual orders payed with Payone for Magento2 shops 2.3.1 and higher
     * Otherwise the core method is used
     *
     * @param  OrigState $subject
     * @param  callable  $proceed
     * @param  Order     $order
     * @return OrigState
     */
    public function aroundCheck(OrigState $subject, callable $proceed, Order $order) {
        if (version_compare($this->shopHelper->getMagentoVersion(), '2.3.0', '<=') || !$order->getPayment()->getMethodInstance() instanceof PayoneMethod || !$order->getIsVirtual()) {
            return $proceed($order);
        }

        $currentState = $order->getState();
        if ($currentState == Order::STATE_NEW && $order->getIsInProcess()) {
            $order->setState(Order::STATE_PROCESSING)->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
            $currentState = Order::STATE_PROCESSING;
        }

        if (!$order->isCanceled() && !$order->canUnhold() && !$order->canInvoice()) {
            // Virtual orders were being falsely set to closed here for authorization orders when the appointed status arrived because canCreditmemo is faulty in Mage 2.3.1 and 2.3.2
            // and because canShip will return false when the order is virtual therefore setStatus(Closed) was removed here for virtual orders
            if ($currentState === Order::STATE_PROCESSING && !$order->canShip()) { // added !$order->getIsVirtual()
                $order->setState(Order::STATE_COMPLETE)->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
            }
        }

        return $subject;
    }
}
