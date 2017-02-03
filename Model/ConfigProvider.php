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

namespace Payone\Core\Model;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Methods\OnlineBankTransfer\Eps;
use Payone\Core\Model\Methods\OnlineBankTransfer\Ideal;

/**
 * Extension for config provider to extend the javascript
 * data-array in the checkout
 */
class ConfigProvider extends \Magento\Payment\Model\CcGenericConfigProvider
{
    /**
     * Payment helper object
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * PAYONE country helper
     *
     * @var \Payone\Core\Helper\Country
     */
    protected $countryHelper;

    /**
     * PAYONE country helper
     *
     * @var \Payone\Core\Helper\Customer
     */
    protected $customerHelper;

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * PAYONE hosted iframe helper
     *
     * @var \Payone\Core\Helper\HostedIframe
     */
    protected $hostedIframeHelper;

    /**
     * PAYONE request helper
     *
     * @var \Payone\Core\Helper\Request
     */
    protected $requestHelper;

    /**
     * Escaper object
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;
    
    /**
     * PAYONE request helper
     *
     * @var \Payone\Core\Helper\Consumerscore
     */
    protected $consumerscoreHelper;

    /**
     * Constructor
     *
     * @param \Magento\Payment\Model\CcConfig   $ccConfig
     * @param \Magento\Payment\Helper\Data      $dataHelper
     * @param \Payone\Core\Helper\Country       $countryHelper
     * @param \Payone\Core\Helper\Customer      $customerHelper
     * @param \Payone\Core\Helper\Payment       $paymentHelper
     * @param \Payone\Core\Helper\HostedIframe  $hostedIframeHelper
     * @param \Payone\Core\Helper\Request       $requestHelper
     * @param \Magento\Framework\Escaper        $escaper
     * @param \Payone\Core\Helper\Consumerscore $consumerscoreHelper
     */
    public function __construct(
        \Magento\Payment\Model\CcConfig $ccConfig,
        \Magento\Payment\Helper\Data $dataHelper,
        \Payone\Core\Helper\Country $countryHelper,
        \Payone\Core\Helper\Customer $customerHelper,
        \Payone\Core\Helper\Payment $paymentHelper,
        \Payone\Core\Helper\HostedIframe $hostedIframeHelper,
        \Payone\Core\Helper\Request $requestHelper,
        \Magento\Framework\Escaper $escaper,
        \Payone\Core\Helper\Consumerscore $consumerscoreHelper
    ) {
        parent::__construct($ccConfig, $dataHelper);
        $this->dataHelper = $dataHelper;
        $this->countryHelper = $countryHelper;
        $this->customerHelper = $customerHelper;
        $this->paymentHelper = $paymentHelper;
        $this->hostedIframeHelper = $hostedIframeHelper;
        $this->requestHelper = $requestHelper;
        $this->escaper = $escaper;
        $this->consumerscoreHelper = $consumerscoreHelper;
    }

    /**
     * Get the payment description text
     *
     * @param  string $sCode
     * @return string
     */
    protected function getInstructionByCode($sCode)
    {
        $oMethodInstance = $this->dataHelper->getMethodInstance($sCode);
        if ($oMethodInstance) {
            return nl2br($this->escaper->escapeHtml($oMethodInstance->getInstructions()));
        }
        return '';
    }

    /**
     * Returns the extended checkout-data array
     *
     * @return array
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        $config = array_merge_recursive($config, [
            'payment' => [
                'payone' => [
                    'availableCardTypes' => $this->paymentHelper->getAvailableCreditcardTypes(),
                    'fieldConfig' => $this->hostedIframeHelper->getHostedFieldConfig(),
                    'sepaCountries' => $this->countryHelper->getDebitSepaCountries(),
                    'hostedRequest' => $this->requestHelper->getHostedIframeRequest(),
                    'mandateManagementActive' => $this->paymentHelper->isMandateManagementActive(),
                    'checkCvc' => (bool)$this->paymentHelper->isCheckCvcActive(),
                    'requestBic' => (bool)$this->requestHelper->getConfigParam('request_bic', PayoneConfig::METHOD_DEBIT, 'payone_payment'),
                    'requestIbanBicSofortUeberweisung' => (bool)$this->requestHelper->getConfigParam('show_iban', PayoneConfig::METHOD_OBT_SOFORTUEBERWEISUNG, 'payone_payment'),
                    'validateBankCode' => (bool)$this->requestHelper->getConfigParam('check_bankaccount', PayoneConfig::METHOD_DEBIT, 'payone_payment'),
                    'bankaccountcheckRequest' => $this->requestHelper->getBankaccountCheckRequest(),
                    'bankCodeValidatedAndValid' => false,
                    'blockedMessage' => $this->paymentHelper->getBankaccountCheckBlockedMessage(),
                    'epsBankGroups' => Eps::getBankGroups(),
                    'idealBankGroups' => Ideal::getBankGroups(),
                    'customerHasGivenGender' => $this->customerHelper->customerHasGivenGender(),
                    'customerHasGivenBirthday' => $this->customerHelper->customerHasGivenBirthday(),
                    'addresscheckEnabled' => (int)$this->requestHelper->getConfigParam('enabled', 'address_check', 'payone_protect'),
                    'addresscheckBillingEnabled' => $this->requestHelper->getConfigParam('check_billing', 'address_check', 'payone_protect') == 'NO' ? 0 : 1,
                    'addresscheckShippingEnabled' => $this->requestHelper->getConfigParam('check_shipping', 'address_check', 'payone_protect') == 'NO' ? 0 : 1,
                    'addresscheckConfirmCorrection' => (int)$this->requestHelper->getConfigParam('confirm_address_correction', 'address_check', 'payone_protect'),
                    'canShowPaymentHintText' => $this->consumerscoreHelper->canShowPaymentHintText(),
                    'paymentHintText' => $this->requestHelper->getConfigParam('payment_hint_text', 'creditrating', 'payone_protect'),
                    'canShowAgreementMessage' => $this->consumerscoreHelper->canShowAgreementMessage(),
                    'agreementMessage' => $this->requestHelper->getConfigParam('agreement_message', 'creditrating', 'payone_protect'),
                ],
            ],
        ]);
        foreach ($this->paymentHelper->getAvailablePaymentTypes() as $sCode) {
            $config['payment']['instructions'][$sCode] = $this->getInstructionByCode($sCode);
        }
        return $config;
    }
}
