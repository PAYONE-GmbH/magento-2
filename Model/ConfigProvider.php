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
     * Privacy declaration object
     *
     * @var \Payone\Core\Model\Api\Payolution\PrivacyDeclaration
     */
    protected $privacyDeclaration;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Resource model for saved payment data
     *
     * @var \Payone\Core\Model\ResourceModel\SavedPaymentData
     */
    protected $savedPaymentData;

    /**
     * Constructor
     *
     * @param \Magento\Payment\Model\CcConfig                      $ccConfig
     * @param \Magento\Payment\Helper\Data                         $dataHelper
     * @param \Payone\Core\Helper\Country                          $countryHelper
     * @param \Payone\Core\Helper\Customer                         $customerHelper
     * @param \Payone\Core\Helper\Payment                          $paymentHelper
     * @param \Payone\Core\Helper\HostedIframe                     $hostedIframeHelper
     * @param \Payone\Core\Helper\Request                          $requestHelper
     * @param \Magento\Framework\Escaper                           $escaper
     * @param \Payone\Core\Helper\Consumerscore                    $consumerscoreHelper
     * @param \Payone\Core\Model\Api\Payolution\PrivacyDeclaration $privacyDeclaration
     * @param \Magento\Checkout\Model\Session                      $checkoutSession
     * @param \Payone\Core\Helper\Shop                             $shopHelper
     * @param \Payone\Core\Model\ResourceModel\SavedPaymentData    $savedPaymentData
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
        \Payone\Core\Helper\Consumerscore $consumerscoreHelper,
        \Payone\Core\Model\Api\Payolution\PrivacyDeclaration $privacyDeclaration,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Model\ResourceModel\SavedPaymentData $savedPaymentData
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
        $this->privacyDeclaration = $privacyDeclaration;
        $this->checkoutSession = $checkoutSession;
        $this->shopHelper = $shopHelper;
        $this->savedPaymentData = $savedPaymentData;
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
     * Add payolution parameters to the config array
     *
     * @return array
     */
    protected function getPayolutionConfig()
    {
        return [
            'b2bMode' => [
                'invoice' => $this->requestHelper->getConfigParam('b2b_mode', PayoneConfig::METHOD_PAYOLUTION_INVOICE, 'payone_payment'),
                'debit' => $this->requestHelper->getConfigParam('b2b_mode', PayoneConfig::METHOD_PAYOLUTION_DEBIT, 'payone_payment'),
                'installment' => $this->requestHelper->getConfigParam('b2b_mode', PayoneConfig::METHOD_PAYOLUTION_INSTALLMENT, 'payone_payment'),
            ],
            'privacyDeclaration' => [
                'invoice' => $this->privacyDeclaration->getPayolutionAcceptanceText(PayoneConfig::METHOD_PAYOLUTION_INVOICE),
                'debit' => $this->privacyDeclaration->getPayolutionAcceptanceText(PayoneConfig::METHOD_PAYOLUTION_DEBIT),
                'installment' => $this->privacyDeclaration->getPayolutionAcceptanceText(PayoneConfig::METHOD_PAYOLUTION_INSTALLMENT),
            ],
        ];
    }

    /**
     * Add payone parameters to the config array
     *
     * @return array
     */
    protected function getPayoneConfig()
    {
        return [
            'availableCardTypes' => $this->paymentHelper->getAvailableCreditcardTypes(),
            'fieldConfig' => $this->hostedIframeHelper->getHostedFieldConfig(),
            'sepaCountries' => $this->countryHelper->getDebitSepaCountries(),
            'hostedRequest' => $this->requestHelper->getHostedIframeRequest(),
            'mandateManagementActive' => $this->paymentHelper->isMandateManagementActive(),
            'checkCvc' => (bool)$this->paymentHelper->isCheckCvcActive(),
            'ccMinValidity' => $this->requestHelper->getConfigParam('min_validity_period', PayoneConfig::METHOD_CREDITCARD, 'payone_payment'),
            'requestBic' => (bool)$this->requestHelper->getConfigParam('request_bic', PayoneConfig::METHOD_DEBIT, 'payone_payment'),
            'requestIbanBicSofortUeberweisung' => (bool)$this->requestHelper->getConfigParam('show_iban', PayoneConfig::METHOD_OBT_SOFORTUEBERWEISUNG, 'payone_payment'),
            'validateBankCode' => (bool)$this->requestHelper->getConfigParam('check_bankaccount', PayoneConfig::METHOD_DEBIT, 'payone_payment'),
            'disableSafeInvoice' => (bool)$this->requestHelper->getConfigParam('disable_after_refusal', PayoneConfig::METHOD_SAFE_INVOICE, 'payone_payment'),
            'bankaccountcheckRequest' => $this->requestHelper->getBankaccountCheckRequest(),
            'bankCodeValidatedAndValid' => false,
            'blockedMessage' => $this->paymentHelper->getBankaccountCheckBlockedMessage(),
            'epsBankGroups' => Eps::getBankGroups(),
            'idealBankGroups' => Ideal::getBankGroups(),
            'customerHasGivenGender' => $this->customerHelper->customerHasGivenGender(),
            'customerBirthday' => $this->customerHelper->getCustomerBirthday(),
            'addresscheckEnabled' => (int)$this->requestHelper->getConfigParam('enabled', 'address_check', 'payone_protect'),
            'addresscheckBillingEnabled' => $this->requestHelper->getConfigParam('check_billing', 'address_check', 'payone_protect') == 'NO' ? 0 : 1,
            'addresscheckShippingEnabled' => $this->requestHelper->getConfigParam('check_shipping', 'address_check', 'payone_protect') == 'NO' ? 0 : 1,
            'addresscheckConfirmCorrection' => (int)$this->requestHelper->getConfigParam('confirm_address_correction', 'address_check', 'payone_protect'),
            'canShowPaymentHintText' => $this->consumerscoreHelper->canShowPaymentHintText(),
            'paymentHintText' => $this->requestHelper->getConfigParam('payment_hint_text', 'creditrating', 'payone_protect'),
            'canShowAgreementMessage' => $this->consumerscoreHelper->canShowAgreementMessage(),
            'agreementMessage' => $this->requestHelper->getConfigParam('agreement_message', 'creditrating', 'payone_protect'),
            'payolution' => $this->getPayolutionConfig(),
            'canceledPaymentMethod' => $this->getCanceledPaymentMethod(),
            'isError' => $this->checkoutSession->getPayoneIsError(),
            'klarnaStoreIds' => $this->paymentHelper->getKlarnaStoreIds(),
            'orderDeferredExists' => (bool)version_compare($this->shopHelper->getMagentoVersion(), '2.1.0', '>='),
            'saveCCDataEnabled' => (bool)$this->requestHelper->getConfigParam('save_data_enabled', PayoneConfig::METHOD_CREDITCARD, 'payone_payment'),
            'savedPaymentData' => $this->savedPaymentData->getSavedPaymentData($this->checkoutSession->getQuote()->getCustomerId()),
        ];
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
                'payone' => $this->getPayoneConfig(),
            ],
        ]);
        foreach ($this->paymentHelper->getAvailablePaymentTypes() as $sCode) {
            $config['payment']['instructions'][$sCode] = $this->getInstructionByCode($sCode);
        }
        return $config;
    }

    /**
     * Get canceled payment method from session
     *
     * @return string|bool
     */
    protected function getCanceledPaymentMethod()
    {
        $sPaymentMethod = $this->checkoutSession->getPayoneCanceledPaymentMethod();
        $this->checkoutSession->unsPayoneCanceledPaymentMethod();
        if ($sPaymentMethod) {
            return $sPaymentMethod;
        }
        return false;
    }
}
