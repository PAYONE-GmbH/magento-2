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

namespace Payone\Core\Observer\Transactionstatus;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Payone\Core\Helper\Mail;
use Payone\Core\Model\PayoneConfig;

/**
 * Event observer for Transactionstatus failed
 */
class Failed implements ObserverInterface
{
    /**
     * PAYONE email helper object
     *
     * @var Mail
     */
    protected $emailHelper = null;

    /**
     * Constructor.
     *
     * @param Mail      $emailHelper
     */
    public function __construct(Mail $emailHelper)
    {
        $this->emailHelper = $emailHelper;
    }

    /**
     * Send amazon hard decline mail to customer
     *
     * @param  Order $oOrder
     * @return void
     */
    protected function sendHardDeclineMail(Order $oOrder)
    {
        $this->emailHelper->sendEmail($oOrder->getCustomerEmail(), 'payone_amazon_hard_decline');
    }

    /**
     * Send the amazon hard decline mail to the customer if needed
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var $oOrder Order */
        $oOrder = $observer->getOrder();

        if ($oOrder->getPayment()->getMethod() == PayoneConfig::METHOD_AMAZONPAY) {
            $this->sendHardDeclineMail($oOrder);
        }
    }
}
