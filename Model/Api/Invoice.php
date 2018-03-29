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

namespace Payone\Core\Model\Api;

use Payone\Core\Model\Api\Request\Base;
use Magento\Sales\Model\Order;

/**
 * Collect all invoice parameters
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
class Invoice
{
    /**
     * Index of added invoice items
     *
     * @var integer
     */
    protected $iIndex = 1;

    /**
     * Invoice amount
     *
     * @var integer
     */
    protected $dAmount = 0;

    /**
     * Vat rate for following entities which may not have the vat attached to it
     *
     * @var double
     */
    protected $dTax = false;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * Request object
     *
     * @var Base
     */
    protected $oRequest;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Toolkit $toolkitHelper Toolkit helper
     */
    public function __construct(\Payone\Core\Helper\Toolkit $toolkitHelper)
    {
        $this->toolkitHelper = $toolkitHelper;
    }

    /**
     * Add parameters for a invoice position
     *
     * @param  string $sId       item identification
     * @param  double $dPrice    item price
     * @param  string $sItemType item type
     * @param  int    $iAmount   item amount
     * @param  string $sDesc     item description
     * @param  double $dVat      item tax rate
     * @return void
     */
    protected function addInvoicePosition($sId, $dPrice, $sItemType, $iAmount, $sDesc, $dVat)
    {
        $this->oRequest->addParameter('id['.$this->iIndex.']', $sId); // add invoice item id
        $this->oRequest->addParameter('pr['.$this->iIndex.']', $this->toolkitHelper->formatNumber($dPrice) * 100); // expected in smallest unit of currency
        $this->oRequest->addParameter('it['.$this->iIndex.']', $sItemType); // add invoice item type
        $this->oRequest->addParameter('no['.$this->iIndex.']', $iAmount); // add invoice item amount
        $this->oRequest->addParameter('de['.$this->iIndex.']', $sDesc); // add invoice item description
        $this->oRequest->addParameter('va['.$this->iIndex.']', $this->toolkitHelper->formatNumber($dVat * 100, 0)); // expected * 100 to also handle vats with decimals
        $this->dAmount += $dPrice * $iAmount; // needed for return of the main method
        $this->iIndex++; // increase index for next item
    }

    /**
     * Add invoicing data to the request and return the summed invoicing amount
     *
     * @param  Base  $oRequest   Request object
     * @param  Order $oOrder     Order object
     * @param  array $aPositions Is given with non-complete captures or debits
     * @param  bool  $blDebit    Is the call coming from a debit request
     * @return integer
     */
    public function addProductInfo(Base $oRequest, Order $oOrder, $aPositions = false, $blDebit = false)
    {
        $this->oRequest = $oRequest; // write request to property for manipulation of the object
        $sInvoiceAppendix = $this->toolkitHelper->getInvoiceAppendix($oOrder); // get invoice appendix
        if (!empty($sInvoiceAppendix)) {// invoice appendix existing?
            $this->oRequest->addParameter('invoiceappendix', $sInvoiceAppendix); // add appendix to request
        }

        $iQtyInvoiced = 0;
        foreach ($oOrder->getAllItems() as $oItem) { // add invoice items for all order items
            if ($oItem->isDummy() === false) { // prevent variant-products of adding 2 items
                $this->addProductItem($oItem, $aPositions); // add product invoice params to request
            }
            $iQtyInvoiced += $oItem->getOrigData('qty_invoiced'); // get data pre-capture
        }

        $blFirstCapture = true; // Is first capture?
        if ($iQtyInvoiced > 0) {
            $blFirstCapture = false;
        }

        if ($aPositions === false || $blFirstCapture === true || $blDebit === true) {
            $this->addShippingItem($oOrder, $aPositions, $blDebit); // add shipping invoice params to request
            $this->addDiscountItem($oOrder, $aPositions, $blDebit); // add discount invoice params to request
        }
        return $this->dAmount;
    }

    /**
     * Add invoicing item for a product
     *
     * @param  \Magento\Sales\Model\Order\Item $oItem
     * @param  array $aPositions
     * @return void
     */
    protected function addProductItem($oItem, $aPositions)
    {
        $sPositionKey = $oItem->getProductId().$oItem->getSku();
        if ($aPositions === false || array_key_exists($sPositionKey, $aPositions) !== false) { // full or single-invoice?
            $dItemAmount = $oItem->getQtyOrdered(); // get ordered item amount
            if ($aPositions !== false && array_key_exists($sPositionKey, $aPositions) !== false) { // product existing in single-invoice?
                $dItemAmount = $aPositions[$sPositionKey]; // use amount from single-invoice
            }
            $iAmount = $this->convertItemAmount($dItemAmount);
            $dPrice = $oItem->getBasePriceInclTax();
            if ($this->toolkitHelper->getConfigParam('currency') == 'display') {
                $dPrice = $oItem->getPriceInclTax();
            }
            $this->addInvoicePosition($oItem->getSku(), $dPrice, 'goods', $iAmount, $oItem->getName(), $oItem->getTaxPercent()); // add invoice params to request
            if ($this->dTax === false) { // is dTax not set yet?
                $this->dTax = $oItem->getTaxPercent(); // set the tax for following entities which dont have the vat attached to it
            }
        }
    }

    /**
     * Add invoicing item for shipping
     *
     * @param  Order $oOrder
     * @param  array $aPositions
     * @param  bool  $blDebit
     * @return void
     */
    protected function addShippingItem(Order $oOrder, $aPositions, $blDebit)
    {
        // shipping costs existing or given for partial captures/debits?
        if ($oOrder->getBaseShippingInclTax() != 0 && ($aPositions === false || ($blDebit === false || array_key_exists('delcost', $aPositions) !== false))) {
            $dPrice = $oOrder->getBaseShippingInclTax();
            if ($this->toolkitHelper->getConfigParam('currency') == 'display') {
                $dPrice = $oOrder->getShippingInclTax();
            }
            if ($aPositions !== false && array_key_exists('delcost', $aPositions) !== false) { // product existing in single-invoice?
                $dPrice = $aPositions['delcost'];
            }
            $sDelDesc = __('Surcharge').' '.__('Shipping Costs'); // default description
            if ($dPrice < 0) { // negative shipping cost
                $sDelDesc = __('Deduction').' '.__('Shipping Costs'); // change item description to deduction
            }
            $sShippingSku = $this->toolkitHelper->getConfigParam('sku', 'costs', 'payone_misc'); // get configured shipping SKU
            $this->addInvoicePosition($sShippingSku, $dPrice, 'shipment', 1, $sDelDesc, $this->dTax); // add invoice params to request
        }
    }

    /**
     * Add invoicing item for discounts
     *
     * @param  Order $oOrder
     * @param  array $aPositions
     * @param  bool  $blDebit
     * @return void
     */
    protected function addDiscountItem(Order $oOrder, $aPositions, $blDebit)
    {
        // discount costs existing or given for partial captures/debit?
        $dTransmitDiscount = $oOrder->getBaseDiscountAmount();
        if ($this->toolkitHelper->getConfigParam('currency') == 'display') {
            $dTransmitDiscount = $oOrder->getDiscountAmount();
        }
        if ($dTransmitDiscount != 0 && $oOrder->getCouponCode() && ($aPositions === false || ($blDebit === false || array_key_exists('oxvoucherdiscount', $aPositions) !== false))) {
            $dDiscount = $this->toolkitHelper->formatNumber($dTransmitDiscount); // format discount
            if ($aPositions === false) {// full invoice?
                // The calculations broken down to single items of Magento2 are unprecise and the Payone API will send an error if
                // the calculated positions don't match, so we compensate for rounding-problems here
                $dTotal = $oOrder->getBaseGrandTotal();
                if ($this->toolkitHelper->getConfigParam('currency') == 'display') {
                    $dTotal = $oOrder->getGrandTotal();
                }
                $dDiff = ($this->dAmount + $dTransmitDiscount - $dTotal); // calc rounding discrepancy
                $dDiscount -= $dDiff; // subtract difference from discount
            }
            $sDiscountSku = $this->toolkitHelper->getConfigParam('sku', 'discount', 'payone_misc'); // get configured discount SKU
            $sDesc = (string)__('Discount'); // default description
            if ($oOrder->getCouponCode()) {// was a coupon code used?
                $sDiscountSku = $this->toolkitHelper->getConfigParam('sku', 'voucher', 'payone_misc'); // get configured voucher SKU
                $sDesc = (string)__('Coupon').' - '.$oOrder->getCouponCode(); // add counpon code to description
            }
            $this->addInvoicePosition($sDiscountSku, $dDiscount, 'voucher', 1, $sDesc, $this->dTax); // add invoice params to request
        }
    }

    /**
     * Check if item amount has decimal places
     * Throw exception if given amount is no integer
     *
     * @param  double $dItemAmount
     * @throws \InvalidArgumentException
     * @return int
     */
    protected function convertItemAmount($dItemAmount)
    {
        if (fmod(floatval($dItemAmount), 1.0) > 0) { // input does not represent an integer
            $sErrorMessage = "Unable to use floating point values for item amounts! Parameter was: ";
            throw new \InvalidArgumentException($sErrorMessage . strval($dItemAmount), 1);
        } else { // return the integer value
            return intval($dItemAmount);
        }
    }
}
