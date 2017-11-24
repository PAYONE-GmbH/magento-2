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

namespace Payone\Core\Model\Api\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Payment\Model\InfoInterface;

/**
 * Class for the PAYONE Server API request "debit"
 */
class Debit extends Base
{
    /**
     * @var \Payone\Core\Model\Api\Invoice $invoiceGenerator
     */
    protected $invoiceGenerator;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Model\Api\Invoice          $invoiceGenerator
     * @param \Payone\Core\Helper\Database            $databaseHelper
     * @param \Payone\Core\Helper\Toolkit             $toolkitHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Model\Api\Invoice $invoiceGenerator,
        \Payone\Core\Helper\Database $databaseHelper,
        \Payone\Core\Helper\Toolkit $toolkitHelper
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog);
        $this->invoiceGenerator = $invoiceGenerator;
        $this->databaseHelper = $databaseHelper;
        $this->toolkitHelper = $toolkitHelper;
    }

    /**
     * Get creditmemo array from request parameters
     *
     * @return mixed
     */
    protected function getCreditmemoRequestParams()
    {
        return $this->shopHelper->getRequestParameter('creditmemo');
    }

    /**
     * Generate position list for invoice data transmission
     *
     * @param Order $oOrder
     * @return array|false
     */
    protected function getInvoiceList(Order $oOrder)
    {
        $aCreditmemo = $this->getCreditmemoRequestParams();

        $aPositions = [];
        $blFull = true;
        if ($aCreditmemo && array_key_exists('items', $aCreditmemo) !== false) {
            foreach ($oOrder->getAllItems() as $oItem) {
                if (isset($aCreditmemo['items'][$oItem->getItemId()]) && $aCreditmemo['items'][$oItem->getItemId()]['qty'] > 0) {
                    $aPositions[$oItem->getProductId().$oItem->getSku()] = $aCreditmemo['items'][$oItem->getItemId()]['qty'];
                    if ($aCreditmemo['items'][$oItem->getItemId()]['qty'] != $oItem->getQtyOrdered()) {
                        $blFull = false;
                    }
                } else {
                    $blFull = false;
                }
            }
        }
        if (isset($aCreditmemo['shipping_amount']) && $aCreditmemo['shipping_amount'] != 0) {
            $aPositions['delcost'] = $aCreditmemo['shipping_amount'];
        }
        if ($blFull === true && (!isset($aCreditmemo['shipping_amount']) || $aCreditmemo['shipping_amount'] == $oOrder->getBaseShippingInclTax())) {
            $aPositions = false; // false = full debit
        }
        return $aPositions;
    }

    /**
     * Send request "debit" to PAYONE server API
     *
     * @param  PayoneMethod  $oPayment
     * @param  InfoInterface $oPaymentInfo
     * @param  float         $dAmount
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, InfoInterface $oPaymentInfo, $dAmount)
    {
        $oOrder = $oPaymentInfo->getOrder();

        $aPositions = $this->getInvoiceList($oOrder);

        $iTxid = $oPaymentInfo->getParentTransactionId();
        if (strpos($iTxid, '-') !== false) {
            $iTxid = substr($iTxid, 0, strpos($iTxid, '-')); // clean the txid from the magento-suffixes
        }

        $this->setOrderId($oOrder->getRealOrderId());

        $this->addParameter('request', 'debit'); // Request method
        $this->addParameter('mode', $oPayment->getOperationMode()); // PayOne Portal Operation Mode (live or test)
        $this->addParameter('txid', $iTxid); // PayOne Transaction ID
        $this->addParameter('sequencenumber', $this->databaseHelper->getSequenceNumber($iTxid));

        // Total order sum in smallest currency unit
        $this->addParameter('amount', number_format((-1 * $dAmount), 2, '.', '') * 100);
        $this->addParameter('currency', $oOrder->getOrderCurrencyCode()); // Currency
        $this->addParameter('transactiontype', 'GT');

        $sRefundAppendix = $this->getRefundAppendix($oOrder, $oPayment);
        if (!empty($sRefundAppendix)) {
            $this->addParameter('invoiceappendix', $sRefundAppendix);
        }

        if ($this->apiHelper->isInvoiceDataNeeded($oPayment)) {
            $this->invoiceGenerator->addProductInfo($this, $oOrder, $aPositions, true); // add invoice parameters
        }

        $aCreditmemo = $this->getCreditmemoRequestParams();
        $sIban = false;
        $sBic = false;
        if (!empty($oOrder->getPayoneRefundIban()) && !empty($oOrder->getPayoneRefundBic())) {
            $sIban = $oOrder->getPayoneRefundIban();
            $sBic = $oOrder->getPayoneRefundBic();
        } elseif (isset($aCreditmemo['payone_iban']) && isset($aCreditmemo['payone_bic'])) {
            $sIban = $aCreditmemo['payone_iban'];
            $sBic = $aCreditmemo['payone_bic'];
        }

        if ($sIban !== false && $sBic !== false && $this->isSepaDataValid($sIban, $sBic)) {
            $this->addParameter('iban', $sIban);
            $this->addParameter('bic', $sBic);
        }

        $aResponse = $this->send($oPayment);

        return $aResponse;
    }

    /**
     * Get substituted refund appendix text
     *
     * @param  Order        $oOrder
     * @param  PayoneMethod $oPayment
     * @return string
     */
    protected function getRefundAppendix(Order $oOrder, PayoneMethod $oPayment)
    {
        $sText = $this->shopHelper->getConfigParam('invoice_appendix_refund', 'invoicing');
        $sCreditMemoIncrId = '';
        $sInvoiceIncrementId = '';
        $sInvoiceId = '';

        $oCreditmemo = $oPayment->getCreditmemo();
        if ($oCreditmemo) {
            $sCreditMemoIncrId = $oCreditmemo->getIncrementId();
            $oInvoice = $oCreditmemo->getInvoice();
            if ($oInvoice) {
                $sInvoiceIncrementId = $oInvoice->getIncrementId();
                $sInvoiceId = $oInvoice->getId();
            }
        }

        $aSubstitutionArray = [
            '{{order_increment_id}}' => $oOrder->getIncrementId(),
            '{{order_id}}' => $oOrder->getId(),
            '{{customer_id}}' => $oOrder->getCustomerId(),
            '{{creditmemo_increment_id}}' => $sCreditMemoIncrId,
            '{{invoice_increment_id}}' => $sInvoiceIncrementId,
            '{{invoice_id}}' => $sInvoiceId,
        ];
        $sRefundAppendix = $this->toolkitHelper->handleSubstituteReplacement($sText, $aSubstitutionArray, 255);
        return $sRefundAppendix;
    }

    /**
     * Validate IBAN
     *
     * @param  string $sIban
     * @return bool
     */
    protected function isIbanValid($sIban)
    {
        $sRegex = '/^[a-zA-Z]{2}[0-9]{2}[a-zA-Z0-9]{4}[0-9]{7}(?:[a-zA-Z0-9]?){0,16}$/';
        return $this->checkRegex($sRegex, $sIban);
    }

    /**
     * Check if the regex validates correctly
     *
     * @param  string $sRegex
     * @param  string $sValue
     * @return bool
     */
    protected function checkRegex($sRegex, $sValue)
    {
        preg_match($sRegex, str_replace(' ', '', $sValue), $aMatches);
        if (empty($aMatches)) {
            return false;
        }
        return true;
    }

    /**
     * Validate IBAN
     *
     * @param  string $sBic
     * @return bool
     */
    protected function isBicValid($sBic)
    {
        $sRegex = '/^([a-zA-Z]{4}[a-zA-Z]{2}[a-zA-Z0-9]{2}([a-zA-Z0-9]{3})?)$/';
        return $this->checkRegex($sRegex, $sBic);
    }

    /**
     * Check IBAN and BIC fields
     *
     * @param  string $sIban
     * @param  string $sBic
     * @return bool
     * @throws LocalizedException
     */
    public function isSepaDataValid($sIban, $sBic)
    {
        if (!$this->isIbanValid($sIban)) {
            throw new LocalizedException(__('The given IBAN is invalid!'));
        }
        if (!$this->isBicValid($sBic)) {
            throw new LocalizedException(__('The given BIC is invalid!'));
        }
        return true;
    }
}
