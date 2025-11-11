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
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Payone\Core\Helper\Order as OrderHelper;
use Payone\Core\Model\PayoneConfig;
use Magento\Framework\App\CacheInterface;

/**
 * Event observer for Transactionstatus paid
 */
class Paid implements ObserverInterface
{
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
     * PAYONE order helper
     *
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * CacheInterface object
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param InvoiceService $invoiceService
     * @param InvoiceSender  $invoiceSender
     * @param OrderHelper    $orderHelper
     * @param CacheInterface $cache
     */
    public function __construct(
        InvoiceService $invoiceService,
        InvoiceSender  $invoiceSender,
        OrderHelper    $orderHelper,
        CacheInterface $cache
    ) {
        $this->invoiceService = $invoiceService;
        $this->invoiceSender  = $invoiceSender;
        $this->orderHelper    = $orderHelper;
        $this->cache          = $cache;
    }

    /**
     * Waits till the appointed lock is removed
     *
     * @param string $sLockKey
     * @return void
     */
    protected function waitTillAppointedIsFinished($sLockKey)
    {
        $iTrys = 10;
        while ($iTrys > 0) {
            sleep(5);
            $iTrys--;
            if (!$this->cache->load($sLockKey)) {
                break;
            }
        }
    }

    /**
     * Generate an invoice for the order to mark the order as paid
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var $oOrder Order */
        $oOrder = $observer->getOrder();

        // order is not guaranteed to exist if using transaction status forwarding
        if (NULL === $oOrder) {
            return;
        }

        $sAppointedLockKey = Appointed::STATUS_LOCK_IDENT.$oOrder->getId();
        if ($this->cache->load($sAppointedLockKey)) { // Appointed status is being processed at the moment
            $this->waitTillAppointedIsFinished($sAppointedLockKey);

            $oOrder = $this->orderHelper->getOrderById($oOrder->getId()); // Reload order because it will have changed in the meantime
        }

        // if advance payment is paid should create an invoice
        if ($oOrder->getPayment()->getMethodInstance()->getCode() === PayoneConfig::METHOD_ADVANCE_PAYMENT) {
            if ($this->orderHelper->getConfigParam('create_invoice', 'payone_advance_payment', 'payone_payment')) {
                $oInvoice = $this->invoiceService->prepareInvoice($oOrder);
                $oInvoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
                $oInvoice->setTransactionId($oOrder->getPayment()->getLastTransId());
                $oInvoice->register();
                $oInvoice->save();

                $oOrder->save();

                if ($this->orderHelper->getConfigParam('send_invoice_email', 'emails')) {
                    $this->invoiceSender->send($oInvoice);
                }
            }
        }

        $aInvoiceList = $oOrder->getInvoiceCollection()->getItems();
        if ($oInvoice = array_shift($aInvoiceList)) { // get first invoice
            $oInvoice->pay(); // mark invoice as paid
            $oInvoice->save();
            $oOrder->save();
        }
    }
}
