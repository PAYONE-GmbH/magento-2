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
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Plugins;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftCard\Observer\GenerateGiftCardAccountsInvoice as GenerateGiftCardAccountsInvoiceOriginal;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Invoice;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Model\PayoneConfig;

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
     * @param GenerateGiftCardAccountsInvoiceOriginal $subject
     * @param callable                                $proceed
     * @param Observer                                $observer
     *
     * @return mixed
     */

    public function aroundExecute(GenerateGiftCardAccountsInvoiceOriginal $subject, callable $proceed, $observer)
    {
        /** @var Invoice $invoice */
        $invoice               = $observer->getInvoice();
        $paymentMethodInstance = $invoice->getOrder()->getPayment()->getMethodInstance();

        if ($paymentMethodInstance instanceof PayoneMethod) {
            try {
                $paymentMethodCode = $paymentMethodInstance->getCode();

                if ($this->isAdvancedPaymentAndInvoiceIsOpen($paymentMethodCode, $invoice) || $this->isAnyOtherPaymentAndInvoiceIsPaid($paymentMethodCode, $invoice)) {
                    return NULL;
                }
            } catch (Exception $exception) {
                // continue with regular plugin flow
            }
        }

        return $proceed($observer);
    }

    private function isAdvancedPaymentAndInvoiceIsOpen(string $paymentMethodCode, Invoice $invoice)
    {
        return $paymentMethodCode === PayoneConfig::METHOD_ADVANCE_PAYMENT && $invoice->getState() === Invoice::STATE_OPEN;
    }

    private function isAnyOtherPaymentAndInvoiceIsPaid(string $paymentMethodCode, Invoice $invoice)
    {
        return $paymentMethodCode !== PayoneConfig::METHOD_ADVANCE_PAYMENT && $invoice->getState() === Invoice::STATE_PAID;
    }
}
