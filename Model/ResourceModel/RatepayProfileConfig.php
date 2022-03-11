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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\ResourceModel;

use Payone\Core\Model\PayoneConfig;

/**
 * Resource model for payone_ratepay_profile_config table
 */
class RatepayProfileConfig extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Map for methodCode to short payment method identifier for database columns
     *
     * @var array
     */
    protected $aMethodIdentifierMap = [
        PayoneConfig::METHOD_RATEPAY_INVOICE => 'invoice',
    ];

    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Payone\Core\Setup\Tables\RatepayProfileConfig::TABLE_RATEPAY_PROFILE_CONFIG, 'id');
    }

    /**
     * Returns all profile configs
     *
     * @return array
     */
    public function getAllProfileConfigs()
    {
        return $this->getProfileConfigsByIds([]);
    }

    /**
     * Get profile configs by given shop ids
     *
     * @param  array $aShopIds
     * @return array
     */
    public function getProfileConfigsByIds($aShopIds)
    {
        $oSelect = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->order('shop_id ASC');

        if (!empty($aShopIds)) {
            $oSelect->where("shop_id IN ('".implode("','", $aShopIds)."')");
        }

        $aResult = $this->getConnection()->fetchAll($oSelect);
        return $aResult;
    }

    /**
     * Checks if given profile config exists in the database
     *
     * @param  string $sShopId
     * @return bool
     */
    public function profileExists($sShopId)
    {
        $aProfileConfigs = $this->getProfileConfigsByIds([$sShopId]);
        if (!empty($aProfileConfigs)) {
            return true;
        }
        return false;
    }

    /**
     * Fills data array for insert and update queries
     *
     * @param  array $aProfileResponse
     * @return array
     */
    protected function getDataArray($aProfileResponse)
    {
        $aData = [
            'profile_id'                            => $aProfileResponse['add_paydata[profile-id]'],
            'merchant_name'                         => $aProfileResponse['add_paydata[merchant-name]'],
            'merchant_status'                       => $aProfileResponse['add_paydata[merchant-status]'],
            'shop_name'                             => $aProfileResponse['add_paydata[shop-name]'],
            'name'                                  => $aProfileResponse['add_paydata[name]'],
            'currency'                              => $aProfileResponse['add_paydata[currency]'],
            'type'                                  => $aProfileResponse['add_paydata[type]'],
            'activation_status_elv'                 => $aProfileResponse['add_paydata[activation-status-elv]'],
            'activation_status_installment'         => $aProfileResponse['add_paydata[activation-status-installment]'],
            'activation_status_invoice'             => $aProfileResponse['add_paydata[activation-status-invoice]'],
            'activation_status_prepayment'          => $aProfileResponse['add_paydata[activation-status-prepayment]'],
            'amount_min_longrun'                    => $aProfileResponse['add_paydata[amount-min-longrun]'],
            'b2b_pq_full'                           => $aProfileResponse['add_paydata[b2b-PQ-full]'],
            'b2b_pq_light'                          => isset($aProfileResponse['add_paydata[b2b-PQ-light]']) ? $aProfileResponse['add_paydata[b2b-PQ-light]'] : '',
            'b2b_elv'                               => $aProfileResponse['add_paydata[b2b-elv]'],
            'b2b_installment'                       => $aProfileResponse['add_paydata[b2b-installment]'],
            'b2b_invoice'                           => $aProfileResponse['add_paydata[b2b-invoice]'],
            'b2b_prepayment'                        => $aProfileResponse['add_paydata[b2b-prepayment]'],
            'country_code_billing'                  => $aProfileResponse['add_paydata[country-code-billing]'],
            'country_code_delivery'                 => $aProfileResponse['add_paydata[country-code-delivery]'],
            'delivery_address_pq_full'              => $aProfileResponse['add_paydata[delivery-address-PQ-full]'],
            'delivery_address_pq_light'             => isset($aProfileResponse['add_paydata[delivery-address-PQ-light]']) ? $aProfileResponse['add_paydata[delivery-address-PQ-light]'] : '',
            'delivery_address_elv'                  => $aProfileResponse['add_paydata[delivery-address-elv]'],
            'delivery_address_installment'          => $aProfileResponse['add_paydata[delivery-address-installment]'],
            'delivery_address_invoice'              => $aProfileResponse['add_paydata[delivery-address-invoice]'],
            'delivery_address_prepayment'           => $aProfileResponse['add_paydata[delivery-address-prepayment]'],
            'device_fingerprint_snippet_id'         => isset($aProfileResponse['add_paydata[device-fingerprint-snippet-id]']) ? $aProfileResponse['add_paydata[device-fingerprint-snippet-id]'] : NULL,
            'eligibility_device_fingerprint'        => isset($aProfileResponse['add_paydata[eligibility-device-fingerprint]']) ? $aProfileResponse['add_paydata[eligibility-device-fingerprint]'] : NULL,
            'eligibility_ratepay_elv'               => isset($aProfileResponse['add_paydata[eligibility-ratepay-elv]']) ? $aProfileResponse['add_paydata[eligibility-ratepay-elv]'] : 'no',
            'eligibility_ratepay_installment'       => isset($aProfileResponse['add_paydata[eligibility-ratepay-installment]']) ? $aProfileResponse['add_paydata[eligibility-ratepay-installment]'] : 'no',
            'eligibility_ratepay_invoice'           => isset($aProfileResponse['add_paydata[eligibility-ratepay-invoice]']) ? $aProfileResponse['add_paydata[eligibility-ratepay-invoice]'] : 'no',
            'eligibility_ratepay_pq_full'           => isset($aProfileResponse['add_paydata[eligibility-ratepay-pq-full]']) ? $aProfileResponse['add_paydata[eligibility-ratepay-pq-full]'] : 'no',
            'eligibility_ratepay_pq_light'          => isset($aProfileResponse['add_paydata[eligibility-ratepay-pq-light]']) ? $aProfileResponse['add_paydata[eligibility-ratepay-pq-light]'] : 'no',
            'eligibility_ratepay_prepayment'        => $aProfileResponse['add_paydata[eligibility-ratepay-prepayment]'],
            'interest_rate_merchant_towards_bank'   => $aProfileResponse['add_paydata[interest-rate-merchant-towards-bank]'],
            'interestrate_default'                  => $aProfileResponse['add_paydata[interestrate-default]'],
            'interestrate_max'                      => $aProfileResponse['add_paydata[interestrate-max]'],
            'interestrate_min'                      => $aProfileResponse['add_paydata[interestrate-min]'],
            'min_difference_dueday'                 => $aProfileResponse['add_paydata[min-difference-dueday]'],
            'month_allowed'                         => $aProfileResponse['add_paydata[month-allowed]'],
            'month_longrun'                         => $aProfileResponse['add_paydata[month-longrun]'],
            'month_number_max'                      => $aProfileResponse['add_paydata[month-number-max]'],
            'month_number_min'                      => $aProfileResponse['add_paydata[month-number-min]'],
            'payment_amount'                        => $aProfileResponse['add_paydata[payment-amount]'],
            'payment_firstday'                      => $aProfileResponse['add_paydata[payment-firstday]'],
            'payment_lastrate'                      => $aProfileResponse['add_paydata[payment-lastrate]'],
            'rate_min_longrun'                      => $aProfileResponse['add_paydata[rate-min-longrun]'],
            'rate_min_normal'                       => $aProfileResponse['add_paydata[rate-min-normal]'],
            'service_charge'                        => $aProfileResponse['add_paydata[service-charge]'],
            'tx_limit_elv_max'                      => isset($aProfileResponse['add_paydata[tx-limit-elv-max]']) ? $aProfileResponse['add_paydata[tx-limit-elv-max]'] : 0,
            'tx_limit_elv_min'                      => isset($aProfileResponse['add_paydata[tx-limit-elv-min]']) ? $aProfileResponse['add_paydata[tx-limit-elv-min]'] : 0,
            'tx_limit_installment_max'              => isset($aProfileResponse['add_paydata[tx-limit-installment-max]']) ? $aProfileResponse['add_paydata[tx-limit-installment-max]'] : 0,
            'tx_limit_installment_min'              => isset($aProfileResponse['add_paydata[tx-limit-installment-min]']) ? $aProfileResponse['add_paydata[tx-limit-installment-min]'] : 0,
            'tx_limit_invoice_max'                  => isset($aProfileResponse['add_paydata[tx-limit-invoice-max]']) ? $aProfileResponse['add_paydata[tx-limit-invoice-max]'] : 0,
            'tx_limit_invoice_min'                  => isset($aProfileResponse['add_paydata[tx-limit-invoice-min]']) ? $aProfileResponse['add_paydata[tx-limit-invoice-min]'] : 0,
            'tx_limit_prepayment_max'               => isset($aProfileResponse['add_paydata[tx-limit-prepayment-max]']) ? $aProfileResponse['add_paydata[tx-limit-prepayment-max]'] : 0,
            'tx_limit_prepayment_min'               => isset($aProfileResponse['add_paydata[tx-limit-prepayment-min]']) ? $aProfileResponse['add_paydata[tx-limit-prepayment-min]'] : 0,
            'valid_payment_firstdays'               => $aProfileResponse['add_paydata[valid-payment-firstdays]'],
        ];
        return $aData;
    }

    /**
     * Updates existing profile config in the database
     *
     * @param  string $sShopId
     * @param  array $aProfileResponse
     * @return void
     */
    public function updateProfileConfig($sShopId, $aProfileResponse)
    {
        $data = $this->getDataArray($aProfileResponse);
        $where = ['shop_id = ?' => $sShopId];
        $this->getConnection()->update($this->getMainTable(), $data, $where);
    }

    /**
     * Insert new line into payone_ratepay_profile_config table
     *
     * @param  string $sShopId
     * @param  array  $aProfileResponse
     * @return void
     */
    public function insertProfileConfig($sShopId, $aProfileResponse)
    {
        $data = $this->getDataArray($aProfileResponse);
        $data['shop_id'] = $sShopId;
        $this->getConnection()->insert($this->getMainTable(), $data);
    }

    /**
     * Returns ratepay method identifier
     *
     * @param  string $sMethodCode
     * @return string|false
     */
    protected function getRatepayMethodIdentifierByMethodCode($sMethodCode)
    {
        if (isset($this->aMethodIdentifierMap[$sMethodCode])) {
            return $this->aMethodIdentifierMap[$sMethodCode];
        }
        return false;
    }

    /**
     * Get matching shop id for current quote parameters
     *
     * @param  string $sMethodCode
     * @param  array $aShopIds
     * @param  string $sCountryCode
     * @param  string $sCurrency
     * @param  double $dGrandTotal
     * @return string|bool
     */
    public function getMatchingShopId($sMethodCode, $aShopIds, $sCountryCode, $sCurrency, $dGrandTotal)
    {
        $sRatepayMethodIdentifier = $this->getRatepayMethodIdentifierByMethodCode($sMethodCode);

        $oSelect = $this->getConnection()->select()
            ->from($this->getMainTable(), ['shop_id'])
            ->where("shop_id IN ('".implode("','", $aShopIds)."')")
            ->where("tx_limit_".$sRatepayMethodIdentifier."_min <= :grandTotal")
            ->where("tx_limit_".$sRatepayMethodIdentifier."_max >= :grandTotal")
            ->where("country_code_billing = :countryCode")
            ->where("currency = :currency")
            ->order('shop_id ASC')
            ->limit(1);

        $aParams = [
            'grandTotal' => $dGrandTotal,
            'countryCode' => $sCountryCode,
            'currency' => $sCurrency,
        ];

        $sShopId = $this->getConnection()->fetchOne($oSelect, $aParams);
        if (empty($sShopId)) {
            return false;
        }
        return $sShopId;
    }
}
