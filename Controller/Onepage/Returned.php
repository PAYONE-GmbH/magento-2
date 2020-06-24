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
use Magento\Framework\App\Request\Http;

/**
 * Controller for handling return from payment provider
 */
class Returned extends \Payone\Core\Controller\ExternalAction
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE substitute order handler
     *
     * @var \Payone\Core\Model\Handler\SubstituteOrder
     */
    protected $substituteOrder;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context         $context
     * @param \Magento\Framework\Data\Form\FormKey          $formKey
     * @param \Magento\Checkout\Model\Session               $checkoutSession
     * @param \Payone\Core\Model\Handler\SubstituteOrder    $substituteOrder
     * @param \Payone\Core\Helper\Database                  $databaseHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Handler\SubstituteOrder $substituteOrder,
        \Payone\Core\Helper\Database $databaseHelper
    ) {
        parent::__construct($context, $formKey);
        $this->checkoutSession = $checkoutSession;
        $this->substituteOrder = $substituteOrder;
        $this->databaseHelper = $databaseHelper;
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
        if (!$order->getId() && !empty($this->getRequest()->getParam('incrementId'))) {
            $sSubstituteIncrementId = $this->databaseHelper->getSubstituteOrderIncrementId($this->getRequest()->getParam('incrementId'));
            if (!empty($sSubstituteIncrementId)) {
                $order->loadByIncrementId($sSubstituteIncrementId);
                $this->substituteOrder->updateCheckoutSession($order);
                return false;
            } else {
                $order->loadByIncrementId($this->getRequest()->getParam('incrementId'));
            }
        }

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
            $this->substituteOrder->createSubstituteOrder($canceledOrder);
        }

        $this->_redirect($this->_url->getUrl('checkout/onepage/success'));
    }
}
