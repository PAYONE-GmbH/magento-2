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

use Magento\Payment\Model\InfoInterface;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Sales\Model\Order;

/**
 * Class for the PAYONE Server API request "capture"
 */
class Capture extends Base
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
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Model\Api\Invoice          $invoiceGenerator
     * @param \Payone\Core\Helper\Database            $databaseHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Model\Api\Invoice $invoiceGenerator,
        \Payone\Core\Helper\Database $databaseHelper
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog);
        $this->invoiceGenerator = $invoiceGenerator;
        $this->databaseHelper = $databaseHelper;
    }

    /**
     * Generate position list for invoice data transmission
     *
     * @param Order $oOrder
     * @return array|false
     */
    protected function getInvoiceList(Order $oOrder)
    {
        $aInvoice = $this->shopHelper->getRequestParameter('invoice');

        $aPositions = [];
        $blFull = true;
        if ($aInvoice && array_key_exists('items', $aInvoice) !== false) {
            foreach ($oOrder->getAllItems() as $oItem) {
                if (isset($aInvoice['items'][$oItem->getItemId()]) && $aInvoice['items'][$oItem->getItemId()] > 0) {
                    $aPositions[$oItem->getProductId().$oItem->getSku()] = $aInvoice['items'][$oItem->getItemId()];
                    if ($aInvoice['items'][$oItem->getItemId()] != $oItem->getQtyOrdered()) {
                        $blFull = false;
                    }
                } else {
                    $blFull = false;
                }
            }
        }
        if ($blFull === true) {
            $aPositions = false;
        }
        return $aPositions;
    }

    /**
     * Send request "capture" to PAYONE server API
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

        $this->setOrderId($oOrder->getRealOrderId());

        $this->addParameter('request', 'capture'); // Request method
        $this->addParameter('mode', $oPayment->getOperationMode()); // PayOne Portal Operation Mode (live or test)
        $this->addParameter('language', $this->shopHelper->getLocale());

        // Total order sum in smallest currency unit
        $this->addParameter('amount', number_format($dAmount, 2, '.', '') * 100);
        $this->addParameter('currency', $oOrder->getOrderCurrencyCode()); // Currency

        $this->addParameter('txid', $iTxid); // PayOne Transaction ID
        $this->addParameter('sequencenumber', $this->databaseHelper->getSequenceNumber($iTxid));

        $this->addParameter('settleaccount', 'auto');

        if ($this->apiHelper->isInvoiceDataNeeded($oPayment)) {
            $this->invoiceGenerator->addProductInfo($this, $oOrder, $aPositions); // add invoice parameters
        }

        $aResponse = $this->send($oPayment);

        return $aResponse;
    }
}
