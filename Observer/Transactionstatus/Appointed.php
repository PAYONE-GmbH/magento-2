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

namespace Payone\Core\Observer\Transactionstatus;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Payone\Core\Helper\Base;
use Payone\Core\Model\PayoneConfig;

/**
 * Event observer for Transactionstatus appointed
 */
class Appointed implements ObserverInterface
{
    /**
     * Logger object
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * OrderSender object
     *
     * @var OrderSender
     */
    protected $orderSender = null;

    /**
     * InvoiceService object
     *
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * InvoiceSender object
     *
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * Payone base helper
     *
     * @var Base
     */
    protected $baseHelper;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param OrderSender     $orderSender
     * @param InvoiceService  $invoiceService
     * @param InvoiceSender   $invoiceSender
     * @param Base            $baseHelper
     */
    public function __construct(
        LoggerInterface $logger,
        OrderSender $orderSender,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Base $baseHelper
    ) {
        $this->logger = $logger;
        $this->orderSender = $orderSender;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Generate an invoice for the order to mark the order as paid
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var $oOrder Order */
        $oOrder = $observer->getOrder();

        // order is not guaranteed to exist if using transaction status forwarding
        if (null === $oOrder) {
            return;
        }

        if (!$oOrder->getEmailSent()) {
            // the email should not have been sent at this given moment,
            // but some custom modules may have changed this behaviour
            try {
                $this->orderSender->send($oOrder);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        // preauthorization-orders and advance payment should not create an invoice
        if ($oOrder->getPayoneAuthmode() != 'authorization' || $oOrder->getPayment()->getMethodInstance()->getCode() == PayoneConfig::METHOD_ADVANCE_PAYMENT){
            return;
        }

        $oInvoice = $this->invoiceService->prepareInvoice($oOrder);
        $oInvoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
        $oInvoice->setTransactionId($oOrder->getPayment()->getLastTransId());
        $oInvoice->register();
        $oInvoice->save();

        $oOrder->save();

        if ($this->baseHelper->getConfigParam('send_invoice_email', 'emails')) {
            $this->invoiceSender->send($oInvoice);
        }
    }
}
