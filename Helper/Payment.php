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

namespace Payone\Core\Helper;

use Payone\Core\Model\PayoneConfig;
use Magento\Framework\Phrase;
use Payone\Core\Model\Source\CreditcardTypes;

/**
 * Helper class for everything that has to do with payment
 */
class Payment extends \Payone\Core\Helper\Base
{
    /**
     * List of all currently available PAYONE payment types
     *
     * @var array
     */
    protected $aAvailablePayments = [
        PayoneConfig::METHOD_CREDITCARD,
        PayoneConfig::METHOD_DEBIT,
        PayoneConfig::METHOD_PAYPAL,
        PayoneConfig::METHOD_CASH_ON_DELIVERY,
        PayoneConfig::METHOD_ADVANCE_PAYMENT,
        PayoneConfig::METHOD_INVOICE,
        PayoneConfig::METHOD_OBT_SOFORTUEBERWEISUNG,
        PayoneConfig::METHOD_OBT_GIROPAY,
        PayoneConfig::METHOD_OBT_EPS,
        PayoneConfig::METHOD_OBT_POSTFINANCE_EFINANCE,
        PayoneConfig::METHOD_OBT_POSTFINANCE_CARD,
        PayoneConfig::METHOD_OBT_IDEAL,
        PayoneConfig::METHOD_OBT_PRZELEWY,
        PayoneConfig::METHOD_BARZAHLEN,
        PayoneConfig::METHOD_PAYDIREKT,
        PayoneConfig::METHOD_SAFE_INVOICE,
        PayoneConfig::METHOD_PAYOLUTION_INVOICE,
        PayoneConfig::METHOD_PAYOLUTION_DEBIT,
        PayoneConfig::METHOD_PAYOLUTION_INSTALLMENT,
        PayoneConfig::METHOD_ALIPAY,
        PayoneConfig::METHOD_AMAZONPAY,
        PayoneConfig::METHOD_KLARNA_BASE,
        PayoneConfig::METHOD_KLARNA_DEBIT,
        PayoneConfig::METHOD_KLARNA_INVOICE,
        PayoneConfig::METHOD_KLARNA_INSTALLMENT,
        PayoneConfig::METHOD_WECHATPAY,
        PayoneConfig::METHOD_RATEPAY_INVOICE,
        PayoneConfig::METHOD_TRUSTLY,
        PayoneConfig::METHOD_APPLEPAY,
    ];

    /**
     * Mapping of payment method code to payment abbreviation
     *
     * @var array
     */
    protected $aPaymentAbbreviation = [
        PayoneConfig::METHOD_CREDITCARD => 'cc',
        PayoneConfig::METHOD_CASH_ON_DELIVERY => 'cod',
        PayoneConfig::METHOD_DEBIT => 'elv',
        PayoneConfig::METHOD_ADVANCE_PAYMENT => 'vor',
        PayoneConfig::METHOD_INVOICE => 'rec',
        PayoneConfig::METHOD_OBT_SOFORTUEBERWEISUNG => 'sb',
        PayoneConfig::METHOD_OBT_GIROPAY => 'sb',
        PayoneConfig::METHOD_OBT_EPS => 'sb',
        PayoneConfig::METHOD_OBT_POSTFINANCE_EFINANCE => 'sb',
        PayoneConfig::METHOD_OBT_POSTFINANCE_CARD => 'sb',
        PayoneConfig::METHOD_OBT_IDEAL => 'sb',
        PayoneConfig::METHOD_OBT_PRZELEWY => 'sb',
        PayoneConfig::METHOD_PAYPAL => 'wlt',
        PayoneConfig::METHOD_PAYDIREKT => 'wlt',
        PayoneConfig::METHOD_BARZAHLEN => 'csh',
        PayoneConfig::METHOD_SAFE_INVOICE => 'rec',
        PayoneConfig::METHOD_PAYOLUTION_INVOICE => 'fnc',
        PayoneConfig::METHOD_PAYOLUTION_DEBIT => 'fnc',
        PayoneConfig::METHOD_PAYOLUTION_INSTALLMENT => 'fnc',
        PayoneConfig::METHOD_ALIPAY => 'wlt',
        PayoneConfig::METHOD_AMAZONPAY => 'wlt',
        PayoneConfig::METHOD_KLARNA_BASE => 'wlt',
        PayoneConfig::METHOD_KLARNA_DEBIT => 'wlt',
        PayoneConfig::METHOD_KLARNA_INVOICE => 'wlt',
        PayoneConfig::METHOD_KLARNA_INSTALLMENT => 'wlt',
        PayoneConfig::METHOD_WECHATPAY => 'wlt',
        PayoneConfig::METHOD_RATEPAY_INVOICE => 'fnc',
        PayoneConfig::METHOD_TRUSTLY => 'sb',
        PayoneConfig::METHOD_APPLEPAY => 'wlt',
    ];

    /**
     * Resource model for saved payment data
     *
     * @var \Payone\Core\Model\ResourceModel\SavedPaymentData
     */
    protected $savedPaymentData;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context             $context
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Payone\Core\Helper\Shop                          $shopHelper
     * @param \Payone\Core\Model\ResourceModel\SavedPaymentData $savedPaymentData
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Model\ResourceModel\SavedPaymentData $savedPaymentData
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->savedPaymentData = $savedPaymentData;
    }

    /**
     * Return available payment types
     *
     * @return array
     */
    public function getAvailablePaymentTypes()
    {
        return $this->aAvailablePayments;
    }

    /**
     * Get all activated creditcard types
     *
     * @return array
     */
    public function getAvailableCreditcardTypes()
    {
        $aReturn = [];

        $sCreditcardTypes = $this->getConfigParam('types', PayoneConfig::METHOD_CREDITCARD, 'payone_payment');
        if ($sCreditcardTypes) {
            $aAllTypes = CreditcardTypes::getCreditcardTypes();

            $aCreditcardTypes = explode(',', $sCreditcardTypes);
            foreach ($aCreditcardTypes as $sTypeId) {
                $aReturn[] = [
                    'id' => $aAllTypes[$sTypeId]['cardtype'],
                    'title' => $aAllTypes[$sTypeId]['name'],
                    'cvc_length' => $aAllTypes[$sTypeId]['cvc_length'],
                ];
            }
        }
        return $aReturn;
    }

    /**
     * Returns configured available apple pay types
     *
     * @return array
     */
    public function getAvailableApplePayTypes()
    {
        $aReturn = [];

        $sApplePayTypes = $this->getConfigParam('types', PayoneConfig::METHOD_APPLEPAY, 'payone_payment');
        if ($sApplePayTypes) {
            return explode(',', $sApplePayTypes);
        }
        return $aReturn;
    }

    /**
     * Return if cvc has to be checked
     *
     * @return bool
     */
    public function isCheckCvcActive()
    {
        return (bool)$this->getConfigParam('check_cvc', PayoneConfig::METHOD_CREDITCARD, 'payone_payment');
    }

    /**
     * Return if mandate management is activated
     *
     * @return bool
     */
    public function isMandateManagementActive()
    {
        return (bool)$this->getConfigParam('sepa_mandate_enabled', PayoneConfig::METHOD_DEBIT, 'payone_payment');
    }

    /**
     * Return if mandate download is activated
     *
     * @return bool
     */
    public function isMandateManagementDownloadActive()
    {
        return (bool)$this->getConfigParam('sepa_mandate_download_enabled', PayoneConfig::METHOD_DEBIT, 'payone_payment');
    }

    /**
     * Get status mapping configuration for given payment type
     *
     * @param  string $sPaymentCode
     * @return array
     */
    public function getStatusMappingByCode($sPaymentCode)
    {
        $sStatusMapping = $this->getConfigParam($sPaymentCode, 'statusmapping');
        $aStatusMapping = $this->unserialize($sStatusMapping);
        $aCleanMapping = [];
        if ($aStatusMapping) {
            foreach ($aStatusMapping as $aMap) {
                if (isset($aMap['txaction']) && isset($aMap['state_status'])) {
                    $aCleanMapping[$aMap['txaction']] = $aMap['state_status'];
                }
            }
        }
        return $aCleanMapping;
    }

    /**
     * Return display-message for the case that the bankaccount check
     * returned, that the given bankaccount was blocked
     *
     * @return Phrase
     */
    public function getBankaccountCheckBlockedMessage()
    {
        $sMessage = $this->getConfigParam('message_response_blocked', PayoneConfig::METHOD_DEBIT, 'payone_payment');
        if (empty($sMessage)) {
            $sMessage = 'Bankdata invalid.';
        }
        return __($sMessage);
    }

    /**
     * Return is PayPal Express is activated in the configuration
     *
     * @return bool
     */
    public function isPayPalExpressActive()
    {
        return (bool)$this->getConfigParam('express_active', PayoneConfig::METHOD_PAYPAL, 'payone_payment');
    }

    /**
     * Get abbreviation for the given payment type
     *
     * @param  string $sPaymentCode
     * @return string
     */
    public function getPaymentAbbreviation($sPaymentCode)
    {
        if (isset($this->aPaymentAbbreviation[$sPaymentCode])) {
            return $this->aPaymentAbbreviation[$sPaymentCode];
        }
        return 'unknown';
    }

    /**
     * Collect the Klarna store ids from the config and format it for frontend-use
     *
     * @return array
     */
    public function getKlarnaStoreIds()
    {
        $aStoreIds = [];
        $aKlarnaConfig = $this->unserialize($this->getConfigParam('klarna_config', PayoneConfig::METHOD_KLARNA, 'payone_payment'));
        if (!is_array($aKlarnaConfig)) {
            return $aStoreIds;
        }

        foreach ($aKlarnaConfig as $aItem) {
            if (!empty($aItem['store_id']) && isset($aItem['countries'])) {
                foreach ($aItem['countries'] as $sCountry) {
                    $aStoreIds[$sCountry] = $aItem['store_id'];
                }
            }
        }
        return $aStoreIds;
    }

    /**
     * Check if given payment method is activated
     *
     * @param  string $sMethodCode
     * @return bool
     */
    public function isPaymentMethodActive($sMethodCode)
    {
        return (bool)$this->getConfigParam('active', $sMethodCode, 'payment');
    }

    /**
     * Get amazon widget url depending on the mode
     *
     * @return string
     */
    public function getAmazonPayWidgetUrl()
    {
        $sSandbox = '';
        if ('test' == $this->getConfigParam('mode', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment')) {
            $sSandbox = '/sandbox';
        }
        return "https://static-eu.payments-amazon.com/OffAmazonPayments/eur".$sSandbox."/lpa/js/Widgets.js";
    }

    /**
     * Returns method titles of Klarna payment methods
     */
    public function getKlarnaMethodTitles()
    {
        return [
            PayoneConfig::METHOD_KLARNA_INVOICE => $this->getConfigParam('title', PayoneConfig::METHOD_KLARNA_INVOICE, 'payment'),
            PayoneConfig::METHOD_KLARNA_DEBIT => $this->getConfigParam('title', PayoneConfig::METHOD_KLARNA_DEBIT, 'payment'),
            PayoneConfig::METHOD_KLARNA_INSTALLMENT => $this->getConfigParam('title', PayoneConfig::METHOD_KLARNA_INSTALLMENT, 'payment'),
        ];
    }
}
