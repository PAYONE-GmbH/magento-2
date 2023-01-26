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
 * @copyright 2003 - 2022 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods\Ratepay;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;

/**
 * Model for Ratepay installment payment method
 */
class Installment extends RatepayBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_RATEPAY_INSTALLMENT;

    /**
     * Payment method sub type
     *
     * @var string
     */
    protected $sSubType = self::METHOD_RATEPAY_SUBTYPE_INSTALLMENT;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'telephone',
        'dateofbirth',
        'iban',
        'bic',
        'installment_amount',
        'installment_number',
        'last_installment_amount',
        'interest_rate',
        'amount',
    ];

    /**
     * Return parameters specific to this payment sub type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getSubTypeSpecificParameters(Order $oOrder)
    {
        $oInfoInstance = $this->getInfoInstance();

        $sIban = $oInfoInstance->getAdditionalInformation('iban');
        $sBic = $oInfoInstance->getAdditionalInformation('bic');

        $sDebitPayType = "BANK-TRANSFER";
        if (!empty($sIban)) {
            $sDebitPayType = "DIRECT-DEBIT";
        }

        $aParams = [
            'add_paydata[debit_paytype]' => $sDebitPayType,
            'add_paydata[installment_number]' => $oInfoInstance->getAdditionalInformation('installment_number'),
            'add_paydata[installment_amount]' => $oInfoInstance->getAdditionalInformation('installment_amount') * 100,
            'add_paydata[last_installment_amount]' => $oInfoInstance->getAdditionalInformation('last_installment_amount') * 100,
            'add_paydata[interest_rate]' => $oInfoInstance->getAdditionalInformation('interest_rate') * 100,
            'add_paydata[amount]' => $oInfoInstance->getAdditionalInformation('amount') * 100,
            'iban' => $sIban,
        ];

        if (!empty($sBic)) {
            $aParams['bic'] = $sBic;
        }
        return $aParams;
    }

    /**
     * Generates allowed installment runtimes array
     *
     * @param  array  $aProfileConfig
     * @param  double $dQuoteTotal
     * @return array
     */
    protected function generateAllowedMonths($aProfileConfig, $dQuoteTotal)
    {
        $dRateMinNormal = $aProfileConfig['rate_min_normal'];
        $aRuntimes = explode(",", $aProfileConfig['month_allowed']);
        $dInterestrateMonth = ((float)$aProfileConfig['interestrate_default'] / 12) / 100;

        $aAllowedRuntimes = [];
        if (!empty($aRuntimes)) {
            foreach ($aRuntimes as $iRuntime) {
                if (!is_numeric($iRuntime)) {
                    continue;
                }
                if ($dInterestrateMonth > 0) { // otherwise division by zero error will happen
                    $dRateAmount = $dQuoteTotal * (($dInterestrateMonth * pow((1 + $dInterestrateMonth), $iRuntime)) / (pow((1 + $dInterestrateMonth), $iRuntime) - 1));
                } else {
                    $dRateAmount = $dQuoteTotal / $iRuntime;
                }

                if ($dRateAmount >= $dRateMinNormal) {
                    $aAllowedRuntimes[] = $iRuntime;
                }
            }
        }
        return $aAllowedRuntimes;
    }

    /**
     * Returns allowed installment runtimes in months
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return array
     */
    public function getAllowedMonths(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote === null) {
            $quote = $this->checkoutSession->getQuote();
        }

        $aConfig = $this->ratepayHelper->getShopConfigByQuote($this->getCode(), $quote);
        if (!$aConfig) {
            return [];
        }
        return $this->generateAllowedMonths($aConfig, $quote->getGrandTotal());
    }
}
