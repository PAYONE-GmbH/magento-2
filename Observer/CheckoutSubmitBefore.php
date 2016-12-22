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

namespace Payone\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Event class to set the orderstatus to new and pending
 */
class CheckoutSubmitBefore implements ObserverInterface
{
    /**
     * PAYONE consumerscore request model
     *
     * @var \Payone\Core\Model\Api\Request\Consumerscore
     */
    protected $consumerscore;

    /**
     * Consumerscore helper
     *
     * @var \Payone\Core\Helper\Consumerscore
     */
    protected $consumerscoreHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\Api\Request\Consumerscore $consumerscore
     * @param \Payone\Core\Helper\Consumerscore            $consumerscoreHelper
     */
    public function __construct(
        \Payone\Core\Model\Api\Request\Consumerscore $consumerscore,
        \Payone\Core\Helper\Consumerscore $consumerscoreHelper
    ) {
        $this->consumerscore = $consumerscore;
        $this->consumerscoreHelper = $consumerscoreHelper;
    }

    /**
     * Get parameter from config
     *
     * @param  string $sParam
     * @param  bool   $blIsAddresscheck
     * @return string
     */
    protected function getConfigParam($sParam, $blIsAddresscheck = false)
    {
        $sGroup = 'creditrating';
        if ($blIsAddresscheck === true) {
            $sGroup = 'address_check';
        }
        return $this->consumerscoreHelper->getConfigParam($sParam, $sGroup, 'payone_protect');
    }

    /**
     * Determine if creditrating is needed
     *
     * @param  Quote $oQuote
     * @return bool
     */
    protected function isCreditratingNeeded(Quote $oQuote)
    {
        if ((bool)$this->getConfigParam('enabled') === false) {
            return false;
        }

        if ($this->getConfigParam('integration_event') != 'after_payment') {
            return false;
        }

        $oMethodInstance = $oQuote->getPayment()->getMethodInstance();

        $sPaymentCode = $oMethodInstance->getCode();
        $sPaymentTypesToCheck = $this->getConfigParam('enabled_for_payment_methods');
        $aPaymentTypesToCheck = explode(',', $sPaymentTypesToCheck);
        if (array_search($sPaymentCode, $aPaymentTypesToCheck) === false) {
            return false;
        }

        if ($oMethodInstance->getInfoInstance()->getAdditionalInformation('payone_boni_agreement') === false) {
            return false;// agreement checkbox was not checked by the customer
        }

        $dTotal = $oQuote->getGrandTotal();
        $dMin = $this->getConfigParam('min_order_total');
        $dMax = $this->getConfigParam('max_order_total');
        if (is_numeric($dMin) && is_numeric($dMax) && ($dTotal < $dMin || $dTotal > $dMax)) {
            return false;
        }

        if ((bool)$this->getConfigParam('sample_mode_enabled')
                && !empty($this->getConfigParam('sample_mode_frequency'))
                && $this->consumerscoreHelper->isSampleNeeded() === false) {
            return false;
        }

        return true;
    }

    protected function isPaymentApplicableForScore(Quote $oQuote, $sScore)
    {
        if ($sScore == 'G') {
            return true;
        }

        $sPaymentCode = $oQuote->getPayment()->getMethodInstance()->getCode();

        $aYellowMethods = $this->consumerscoreHelper->getAllowedMethodsForScore('Y');
        $aRedMethods = $this->consumerscoreHelper->getAllowedMethodsForScore('R');

        if ($sScore == 'Y' && (array_search($sPaymentCode, $aYellowMethods) !== false ||
                array_search($sPaymentCode, $aRedMethods) !== false)) {
            return true;
        } elseif ($sScore == 'R' && array_search($sPaymentCode, $aRedMethods) !== false) {
            return true;
        }
        return false;
    }

    /**
     * Filter payment methods by the creditrating result if applicable
     *
     * @param  AddressInterface $oBilling
     * @return void
     */
    protected function getScoreByCreditrating(AddressInterface $oBilling)
    {
        $aResponse = $this->consumerscore->sendRequest($oBilling);
        if ($aResponse === true) {// creditrating not executed because of a previous check
            $this->consumerscoreHelper->copyOldStatusToNewAddress($oBilling);
        }

        if (!$aResponse || (isset($aResponse['status']) && $aResponse['status'] == 'ERROR'
                && $this->getConfigParam('handle_response_error') == 'stop_checkout')) {
            $sErrorMsg = $this->getConfigParam('stop_checkout_message');
            if (empty($sErrorMsg)) {
                $sErrorMsg = 'An error occured during the credit check.';
            }
            throw new LocalizedException(__($sErrorMsg));
        }

        if (isset($aResponse['score'])) {
            $oBilling->setPayoneProtectScore($aResponse['score'])->save();
        }

        $sScore = $oBilling->getPayoneProtectScore();
        return $sScore;
    }

    /**
     * Execute certain tasks after the payment is placed and thus the order is placed
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $oQuote = $observer->getQuote();
        $oBilling = $oQuote->getBillingAddress();
        $oShipping = $oQuote->getShippingAddress();

        $aScores = [];
        if ($this->getConfigParam('enabled', true)) {// is addresscheck active
            $aScores[] = $oBilling->getPayoneAddresscheckScore();
            $aScores[] = $oShipping->getPayoneAddresscheckScore();
        }

        if ($this->isCreditratingNeeded($oQuote) === true) {
            $aScores[] = $this->getScoreByCreditrating($oBilling);
        }

        $sScore = $this->consumerscoreHelper->getWorstScore($aScores);
        $blSuccess = $this->isPaymentApplicableForScore($oQuote, $sScore);
        if ($blSuccess === false) {
            throw new LocalizedException(__('An error occured during the credit check.'));
        }
    }
}
