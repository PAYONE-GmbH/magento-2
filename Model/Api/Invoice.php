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
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote;

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
     * PAYONE amasty helper
     *
     * @var \Payone\Core\Helper\AmastyGiftcard
     */
    protected $amastyHelper;

    /**
     * Request object
     *
     * @var Base
     */
    protected $oRequest;

    /**
     * Current store code
     *
     * @var string
     */
    protected $sStoreCode;

    /**
     * Determines if price has to be negated
     *
     * @var bool
     */
    protected $blNegatePrice = false;

    /**
     * Determines if product category url has to be send
     *
     * @var bool
     */
    protected $blSendCategoryUrl = false;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Toolkit $toolkitHelper Toolkit helper
     */
    public function __construct(\Payone\Core\Helper\Toolkit $toolkitHelper, \Payone\Core\Helper\AmastyGiftcard $amastyHelper)
    {
        $this->toolkitHelper = $toolkitHelper;
        $this->amastyHelper = $amastyHelper;
    }

    /**
     * @param bool $blNegatePrice
     */
    public function setNegatePrice($blNegatePrice)
    {
        $this->blNegatePrice = $blNegatePrice;
    }

    /**
     * @param bool $blSendCategoryUrl
     */
    public function setSendCategoryUrl($blSendCategoryUrl)
    {
        $this->blSendCategoryUrl = $blSendCategoryUrl;
    }

    /**
     * Add parameters for a invoice position
     *
     * @param  string $sId          item identification
     * @param  double $dPrice       item price
     * @param  string $sItemType    item type
     * @param  int    $iAmount      item amount
     * @param  string $sDesc        item description
     * @param  double $dVat         item tax rate
     * @param  string $sCategoryUrl category url
     * @return void
     */
    protected function addInvoicePosition($sId, $dPrice, $sItemType, $iAmount, $sDesc, $dVat, $sCategoryUrl = false)
    {
        $iMultiplier = 1;
        if ($this->blNegatePrice === true) {
            $iMultiplier = -1;
        }
        $this->oRequest->addParameter('id['.$this->iIndex.']', $this->formatSku($sId)); // add invoice item id
        $this->oRequest->addParameter('pr['.$this->iIndex.']', $this->toolkitHelper->formatNumber($dPrice) * 100 * $iMultiplier); // expected in smallest unit of currency
        $this->oRequest->addParameter('it['.$this->iIndex.']', $sItemType); // add invoice item type
        $this->oRequest->addParameter('no['.$this->iIndex.']', $iAmount); // add invoice item amount
        $this->oRequest->addParameter('de['.$this->iIndex.']', $sDesc); // add invoice item description
        $this->oRequest->addParameter('va['.$this->iIndex.']', $this->toolkitHelper->formatNumber($dVat * 100, 0)); // expected * 100 to also handle vats with decimals
        if ($sCategoryUrl !== false) {
            $this->oRequest->addParameter('add_paydata[category_path_'.$this->iIndex.']', $sCategoryUrl); // add category url of a product, needed for BNPL payment methods
        }
        $this->dAmount += $dPrice * $iAmount; // needed for return of the main method
        $this->iIndex++; // increase index for next item
    }

    /**
     * Add invoicing data to the request and return the summed invoicing amount
     *
     * @param  Base     $oRequest       Request object
     * @param  object   $oOrder         Order object
     * @param  array    $aPositions     Is given with non-complete captures or debits
     * @param  bool     $blDebit        Is the call coming from a debit request
     * @param  double   $dShippingCosts Shipping costs - needed for Klarna start_session
     * @return integer
     */
    public function addProductInfo(Base $oRequest, $oOrder, $aPositions = false, $blDebit = false, $dShippingCosts = false)
    {
        $this->oRequest = $oRequest; // write request to property for manipulation of the object
        $this->setStoreCode($oOrder->getStore()->getCode());
        if ($oOrder instanceof Order) {
            $sInvoiceAppendix = $this->toolkitHelper->getInvoiceAppendix($oOrder); // get invoice appendix
            if (!empty($sInvoiceAppendix)) { // invoice appendix existing?
                $this->oRequest->addParameter('invoiceappendix', $sInvoiceAppendix); // add appendix to request
            }
        }

        $iQtyInvoiced = 0;
        foreach ($oOrder->getAllItems() as $oItem) { // add invoice items for all order items
            if (($oOrder instanceof Order && $oItem->isDummy() === false) || ($oOrder instanceof Quote && $oItem->getParentItemId() === null)) { // prevent variant-products of adding 2 items
                $this->addProductItem($oItem, $aPositions); // add product invoice params to request
            }
            $iQtyInvoiced += $oItem->getOrigData('qty_invoiced'); // get data pre-capture
        }

        $blFirstCapture = true; // Is first capture?
        if ($iQtyInvoiced > 0) {
            $blFirstCapture = false;
        }

        if ($aPositions === false || $blFirstCapture === true || $blDebit === true) {
            $this->addShippingItem($oOrder, $aPositions, $blDebit, $dShippingCosts); // add shipping invoice params to request
            $this->addGiftCardItem($oOrder);  // add gift card invoice params to request
            $this->addAmastyGiftcards($oOrder, $aPositions, $blDebit); // add amasty giftcard invoice params to request
        }
        $this->addDiscountItem($oOrder, $aPositions, $blDebit); // add discount invoice params to request

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
            if ($oItem instanceof QuoteItem) {
                $dItemAmount = $oItem->getQty();
            }
            if ($aPositions !== false && array_key_exists($sPositionKey, $aPositions) !== false) { // product existing in single-invoice?
                $dItemAmount = $aPositions[$sPositionKey]; // use amount from single-invoice
            }
            $iAmount = $this->convertItemAmount($dItemAmount);
            $dPrice = $oItem->getBasePriceInclTax();
            if ($this->toolkitHelper->getConfigParam('currency', 'global', 'payone_general', $this->getStoreCode()) == 'display') {
                $dPrice = $oItem->getPriceInclTax();
            }

            $sCategoryUrl = false;
            if ($this->blSendCategoryUrl === true) {
                $sCategoryUrl = $this->getProductCategoryUrl($oItem);
            }

            $this->addInvoicePosition($oItem->getSku(), $dPrice, 'goods', $iAmount, $oItem->getName(), $oItem->getTaxPercent(), $sCategoryUrl); // add invoice params to request
            if ($this->dTax === false) { // is dTax not set yet?
                $this->dTax = $oItem->getTaxPercent(); // set the tax for following entities which dont have the vat attached to it
            }
        }
    }

    /**
     * Try to get a category url from given order item
     *
     * @param  \Magento\Sales\Model\Order\Item $oItem
     * @return string|false
     */
    protected function getProductCategoryUrl($oItem)
    {
        $oProduct = $oItem->getProduct();
        if ($oProduct) {
            $oCategoryCollection = $oProduct->getCategoryCollection();
            if (count($oCategoryCollection) > 0) {
                $oCategory = $oCategoryCollection->getFirstItem();
                if ($oCategory) {
                    $sUrl = $oCategory->getUrl();
                    if (!empty($sUrl)) {
                        return $sUrl;
                    }
                }
            }
        }
        return false;
    }

    protected function addGiftCardItem($oOrder)
    {
        $giftCards = json_decode($oOrder->getData('gift_cards') ?? '', true);

        if(empty($giftCards) || !is_array($giftCards)) {
            return;
        }

        foreach($giftCards as $giftCard) {
            $giftCardAmount = $this->getGiftCardAmount($giftCard);
            $this->addInvoicePosition($giftCard['c'], $giftCardAmount, 'voucher', 1, 'Giftcard', 0);
        }
    }

    /**
     * return giftcard-amount based on magento version
     *
     * @param $aGiftCard
     * @return
     */
    private function getGiftCardAmount($aGiftCard)
    {
        // up to Magento 2.3.4 giftcard-amount is saved in 'authorized', again in 2.4
        if (array_key_exists('authorized', $aGiftCard)) {
            return -$aGiftCard['authorized'];
        }
        // in Magento 2.3.5 the array has slightly changed, giftcard-amount is only saved in 'ba'
        if (array_key_exists('ba', $aGiftCard)) {
            return -$aGiftCard['ba'];
        }
        return 0;
    }

    /**
     * Add invoicing item for shipping
     *
     * @param  Order    $oOrder
     * @param  array    $aPositions
     * @param  bool     $blDebit
     * @param  double   $dShippingCosts
     * @return void
     */
    protected function addShippingItem($oOrder, $aPositions, $blDebit, $dShippingCosts = false)
    {
        $dPrice = $dShippingCosts;
        if ($dPrice === false) {
            $dPrice = $oOrder->getBaseShippingInclTax();
            if ($this->toolkitHelper->getConfigParam('currency', 'global', 'payone_general', $this->getStoreCode()) == 'display') {
                $dPrice = $oOrder->getShippingInclTax();
            }
        }

        // shipping costs existing or given for partial captures/debits?
        if ($dPrice != 0 && ($aPositions === false || ($blDebit === false || array_key_exists('delcost', $aPositions) !== false))) {
            if ($aPositions !== false && array_key_exists('delcost', $aPositions) !== false) { // product existing in single-invoice?
                $dPrice = $aPositions['delcost'];
            }
            $sDelDesc = __('Surcharge').' '.__('Shipping Costs'); // default description
            if ($dPrice < 0) { // negative shipping cost
                $sDelDesc = __('Deduction').' '.__('Shipping Costs'); // change item description to deduction
            }
            $sShippingSku = $this->toolkitHelper->getConfigParam('sku', 'costs', 'payone_misc', $this->getStoreCode()); // get configured shipping SKU
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
    protected function addDiscountItem($oOrder, $aPositions, $blDebit)
    {
        // discount costs existing or given for partial captures/debit?
        $dTransmitDiscount = $oOrder->getBaseDiscountAmount();
        if ($this->toolkitHelper->getConfigParam('currency', 'global', 'payone_general', $this->getStoreCode()) == 'display') {
            $dTransmitDiscount = $oOrder->getDiscountAmount();
        }

        if ($oOrder instanceof Quote) {
            $dTransmitDiscount = $oOrder->getBaseSubtotal() - $oOrder->getBaseSubtotalWithDiscount();
            if ($this->toolkitHelper->getConfigParam('currency', 'global', 'payone_general', $this->getStoreCode()) == 'display') {
                $dTransmitDiscount = $oOrder->getSubtotal() - $oOrder->getSubtotalWithDiscount();
            }
        }

        if ($dTransmitDiscount != 0 && ($aPositions === false || ($blDebit === false || array_key_exists('discount', $aPositions) !== false))) {
            if ($aPositions !== false && array_key_exists('discount', $aPositions) !== false) {
                $dTransmitDiscount = $aPositions['discount'];
            }
            $dDiscount = $this->toolkitHelper->formatNumber($dTransmitDiscount); // format discount
            if ($aPositions === false && $this->amastyHelper->hasAmastyGiftcards($oOrder->getQuoteId(), $oOrder) === false) {
                // The calculations broken down to single items of Magento2 are unprecise and the Payone API will send an error if
                // the calculated positions don't match, so we compensate for rounding-problems here
                $dTotal = $oOrder->getBaseGrandTotal();
                if ($this->toolkitHelper->getConfigParam('currency', 'global', 'payone_general', $this->getStoreCode()) == 'display') {
                    $dTotal = $oOrder->getGrandTotal();
                }
                $dDiff = ($this->dAmount + $dTransmitDiscount - $dTotal); // calc rounding discrepancy
                $dDiscount -= $dDiff; // subtract difference from discount
            }
            $sDiscountSku = $this->toolkitHelper->getConfigParam('sku', 'discount', 'payone_misc', $this->getStoreCode()); // get configured discount SKU
            $sDesc = (string)__('Discount'); // default description
            if ($oOrder->getCouponCode()) {// was a coupon code used?
                $sDiscountSku = $this->toolkitHelper->getConfigParam('sku', 'voucher', 'payone_misc', $this->getStoreCode()); // get configured voucher SKU
                $sDesc = (string)__('Coupon').' - '.$oOrder->getCouponCode(); // add counpon code to description
            }
            $this->addInvoicePosition($sDiscountSku, $dDiscount, 'voucher', 1, $sDesc, $this->dTax); // add invoice params to request
        }
    }

    /**
     * Adding amasty giftcards to request
     *
     * @param  Order $oOrder
     * @param  array $aPositions
     * @param  bool  $blDebit
     * @return void
     */
    protected function addAmastyGiftcards($oOrder, $aPositions, $blDebit)
    {
        $aGiftCards = $this->amastyHelper->getAmastyGiftCards($oOrder->getQuoteId(), $oOrder);
        for ($i = 0; $i < count($aGiftCards); $i++) {
            $aGiftCard = $aGiftCards[$i];
            $blIsLastGiftcard = false;
            if ($i + 1 == count($aGiftCards)) {
                $blIsLastGiftcard = true;
            }

            $dTransmitDiscount = $aGiftCard['base_gift_amount'];
            if ($this->toolkitHelper->getConfigParam('currency', 'global', 'payone_general', $this->getStoreCode()) == 'display') {
                $dTransmitDiscount = $aGiftCard['gift_amount'];
            }
            if ($dTransmitDiscount != 0 && ($aPositions === false || ($blDebit === false || array_key_exists('discount', $aPositions) !== false))) {
                $dTransmitDiscount = $dTransmitDiscount * -1;
                $dDiscount = $this->toolkitHelper->formatNumber($dTransmitDiscount); // format discount
                if ($aPositions === false && $blIsLastGiftcard === true) {
                    // The calculations broken down to single items of Magento2 are unprecise and the Payone API will send an error if
                    // the calculated positions don't match, so we compensate for rounding-problems here
                    $dTotal = $oOrder->getBaseGrandTotal();
                    if ($this->toolkitHelper->getConfigParam('currency', 'global', 'payone_general', $this->getStoreCode()) == 'display') {
                        $dTotal = $oOrder->getGrandTotal();
                    }
                    $dDiff = ($this->dAmount + $dTransmitDiscount - $dTotal); // calc rounding discrepancy
                    $dDiscount -= $dDiff; // subtract difference from discount
                }

                if ($dDiscount != 0) {
                    $sDiscountSku = $this->toolkitHelper->getConfigParam('sku', 'voucher', 'payone_misc', $this->getStoreCode()); // get configured voucher SKU
                    $sDesc = (string)__('Amasty Coupon');
                    $this->addInvoicePosition($sDiscountSku, $dDiscount, 'voucher', 1, $sDesc, $this->dTax); // add invoice params to request
                }
            }
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

    /**
     * Set store code
     *
     * @param  $sStoreCode
     * @return void
     */
    protected function setStoreCode($sStoreCode)
    {
        $this->sStoreCode = $sStoreCode;
    }

    /**
     * Returns store code
     *
     * @return string
     */
    protected function getStoreCode()
    {
        return $this->sStoreCode;
    }
    
    /**
     * Returns formatted sku
     *
     * @param string $sSku
     * @return string
     */
    protected function formatSku($sSku)
    {
        $sSku = str_replace(',', '', $sSku); // remove comma from sku
        $sSku = substr($sSku, 0, 32); // limit sku to 32 chars
        return $sSku;
    }
}
