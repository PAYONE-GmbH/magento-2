<?php
/**
 * Short description for class
 *
 * Long description for class (if any)...
 *
 * @package    payone.ba
 * @author     Rico Neitzel, Buro 71a <info@buro71a.de>
 * @copyright  2020 Buro 71a, Rico Neitzel und Tobias Klose GbR
 */

namespace Payone\Core\Model\Plugins;


use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Invoice;
use Payone\Core\Model\Methods\PayoneMethod;

class GenerateGiftCardAccountsInvoice
{
    /**
     *
     * PayOne saves the invoices multiple times (appointed and paid).
     * Magento creates a gift card account everytime the invoice is saved.
     *
     * Only proceed with original Observer if invoice is paid
     * to avoid duplicate gift card accounts
     *
     * @param \Magento\GiftCard\Observer\GenerateGiftCardAccountsInvoice $subject
     * @param callable                                                   $proceed
     * @param Observer                                                   $observer
     *
     * @return mixed
     */

    public function aroundExecute(\Magento\GiftCard\Observer\GenerateGiftCardAccountsInvoice $subject, callable $proceed, $observer)
    {
        /** @var Invoice $invoice */
        $invoice       = $observer->getInvoice();
        $paymentMethod = $invoice->getOrder()->getPayment()->getMethodInstance();
        if ($paymentMethod instanceof PayoneMethod && $invoice->getState() === Invoice::STATE_PAID) {
            return $proceed($observer);
        }
    }
}
