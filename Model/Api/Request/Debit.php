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
     * Send request "debit" to PAYONE server API
     *
     * @param  PayoneMethod  $oPayment
     * @param  InfoInterface $oPaymentInfo
     * @param  float         $dAmount
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, InfoInterface $oPaymentInfo, $dAmount)
    {
        $aCreditmemo = $this->shopHelper->getRequestParameter('creditmemo');
        ob_start();
        print_r($aCreditmemo);
        error_log(ob_get_contents());
        ob_end_clean();

        return false;

        $oOrder = $oPaymentInfo->getOrder();
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
            $this->invoiceGenerator->addProductInfo($this, $oOrder); // add invoice parameters
        }

        // Add debit bank data given - see oxid integration
        // Add invoice data if needed - see oxid integration

        $aResponse = $this->send();

        // Save which positions have been debited - see oxid integration

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
}
