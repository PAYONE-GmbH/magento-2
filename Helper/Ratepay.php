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

namespace Payone\Core\Helper;

use Payone\Core\Model\PayoneConfig;

/**
 * Helper class for ratepay payment
 */
class Ratepay extends \Payone\Core\Helper\Base
{
    /**
     * Map for methodCode to short payment method identifier for database columns
     *
     * @var array
     */
    protected $aMethodIdentifierMap = [
        PayoneConfig::METHOD_RATEPAY_INVOICE => 'invoice',
        PayoneConfig::METHOD_RATEPAY_DEBIT => 'elv',
        PayoneConfig::METHOD_RATEPAY_INSTALLMENT => 'installment',
    ];

    /**
     * Object of profile request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\Profile
     */
    protected $profile;

    /**
     * Ratepay profile resource model
     *
     * @var \Payone\Core\Model\ResourceModel\RatepayProfileConfig
     */
    protected $profileResource;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Payone API helper
     *
     * @var \Payone\Core\Helper\Api
     */
    protected $apiHelper;

    /**
     * Payone Payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context                 $context
     * @param \Magento\Store\Model\StoreManagerInterface            $storeManager
     * @param \Payone\Core\Helper\Shop                              $shopHelper
     * @param \Magento\Framework\App\State                          $state
     * @param \Payone\Core\Model\Api\Request\Genericpayment\Profile $profile
     * @param \Payone\Core\Model\ResourceModel\RatepayProfileConfig $profileResource
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     * @param \Payone\Core\Helper\Api                               $apiHelper
     * @param \Payone\Core\Helper\Payment                           $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\State $state,
        \Payone\Core\Model\Api\Request\Genericpayment\Profile $profile,
        \Payone\Core\Model\ResourceModel\RatepayProfileConfig $profileResource,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Helper\Payment $paymentHelper
    ) {
        parent::__construct($context, $storeManager, $shopHelper, $state);
        $this->profile = $profile;
        $this->profileResource = $profileResource;
        $this->checkoutSession = $checkoutSession;
        $this->apiHelper = $apiHelper;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Returns json decoded array with ratepay shop config by given payment method
     *
     * @param  string $sPaymentMethod
     * @return array
     */
    public function getRatepayShopConfigByPaymentMethod($sPaymentMethod)
    {
        return $this->getRatepayShopConfigByPath("payone_payment/".$sPaymentMethod."/ratepay_shop_config");
    }

    /**
     * Extract shop_ids from configured shop ids for given payment method
     *
     * @param  string $sPaymentMethod
     * @return array
     */
    public function getRatepayShopConfigIdsByPaymentMethod($sPaymentMethod)
    {
        $aShopConfig = $this->getRatepayShopConfigByPaymentMethod($sPaymentMethod);

        $aShopIds = [];
        foreach ($aShopConfig as $aConfig) {
            if (!empty($aConfig['shop_id'])) {
                $aShopIds[] = $aConfig['shop_id'];
            }
        }

        return $aShopIds;
    }

    /**
     * Returns json decoded array with ratepay shop config id by full config path
     *
     * @param  string $sPath
     * @return array
     */
    public function getRatepayShopConfigByPath($sPath)
    {
        $aReturn = [];

        $sShopConfig = $this->getConfigParamByPath($sPath);
        if (!empty($sShopConfig)) {
            $aShopConfig = json_decode($sShopConfig, true);
            if (is_array($aShopConfig)) {
                foreach ($aShopConfig as $aConfig) {
                    if (!empty($aConfig['shop_id'])) {
                        $aReturn[] = $aConfig;
                    }
                }
            }
        }
        return $aReturn;
    }

    /**
     * Extract payment method from config path
     *
     * @param  string $sPath
     * @return bool|mixed
     */
    public function getPaymentMethodFromPath($sPath)
    {
        preg_match("/payone_payment\/(.*)\/ratepay_shop_config/", $sPath, $aMatch);
        if (is_array($aMatch) && isset($aMatch[1])) {
            return $aMatch[1];
        }
        return false;
    }

    /**
     * Imports new profile configuration
     *
     * @param  string $sShopId
     * @param  string $sCurrency
     * @param  string $sMethodCode
     * @return void
     */
    public function importProfileConfiguration($sShopId, $sCurrency, $sMethodCode)
    {
        if ($this->profileResource->profileExists($sShopId) === false) {
            $sMode = $this->getConfigParam('mode', $sMethodCode, 'payone_payment');
            $aResult = $this->profile->sendRequest($sShopId, $sCurrency, $sMode);
            if (isset($aResult['status'])) {
                if ($aResult['status'] == 'OK') {
                    $this->profileResource->insertProfileConfig($sShopId, $aResult);
                }
            }
        }
    }

    /**
     * Refreshes all of the profile configs of the given payment method
     *
     * @param  $sMethodCode
     * @return void
     */
    public function refreshProfiles($sMethodCode)
    {
        $sMode = $this->getConfigParam('mode', $sMethodCode, 'payone_payment');

        $aShopIds = $this->getRatepayShopConfigByPaymentMethod($sMethodCode);
        foreach ($aShopIds as $aConfig) {
            $aResult = $this->profile->sendRequest($aConfig['shop_id'], $aConfig['currency'], $sMode);
            if (!isset($aResult['status']) || $aResult['status'] != 'OK') {
                throw new \Exception("An error occured".(!empty($aResult['errormessage']) ? ": ".$aResult['errormessage'] : ""));
            }

            $this->profileResource->updateProfileConfig($aConfig['shop_id'], $aResult);
        }
    }

    /**
     * Generates device fingerprint token vom customer id and time
     *
     * @return string
     */
    protected function generateDeviceFingerprintToken()
    {
        return md5($this->checkoutSession->getQuote()->getCustomer()->getId().'_'.microtime());
    }

    /**
     * Generates Ratepay device fingerprint token or takes it from the checkout session
     *
     * @return string
     */
    public function getRatepayDeviceFingerprintToken()
    {
        $sTokenFromSession = $this->checkoutSession->getPayoneRatepayDeviceFingerprintToken();
        if (empty($sTokenFromSession)) {
            $sTokenFromSession = $this->generateDeviceFingerprintToken();
            $this->checkoutSession->setPayoneRatepayDeviceFingerprintToken($sTokenFromSession);
        }
        return $sTokenFromSession;
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
     * Get matching Ratepay shop id for current transaction
     *
     * @param  string $sMethodCode
     * @param  string $sCountryCode
     * @param  string $sCurrency
     * @param  double $dGrandTotal
     * @param  bool $blGetConfigWithoutTotals
     * @return string|false
     */
    public function getRatepayShopId($sMethodCode, $sCountryCode, $sCurrency, $dGrandTotal, $blGetConfigWithoutTotals = false)
    {
        $sRatepayMethodIdentifier = $this->getRatepayMethodIdentifierByMethodCode($sMethodCode);

        $aShopIds = $this->getRatepayShopConfigIdsByPaymentMethod($sMethodCode);
        return $this->profileResource->getMatchingShopId($sRatepayMethodIdentifier, $aShopIds, $sCountryCode, $sCurrency, $dGrandTotal, $blGetConfigWithoutTotals);
    }

    /**
     * Returns matching Ratepay shop id by given quote
     *
     * @param  string $sMethodCode
     * @param  \Magento\Quote\Api\Data\CartInterface $quote
     * @param  bool $blGetConfigWithoutTotals
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShopIdByQuote($sMethodCode, \Magento\Quote\Api\Data\CartInterface $quote, $blGetConfigWithoutTotals = false)
    {
        $sCountryCode = $quote->getShippingAddress()->getCountryId();
        if (empty($sCountryCode)) {
            $sCountryCode = $quote->getBillingAddress()->getCountryId();
        }
        $sCurrency = $this->apiHelper->getCurrencyFromQuote($quote);
        $dGrandTotal = $this->apiHelper->getQuoteAmount($quote);

        return $this->getRatepayShopId($sMethodCode, $sCountryCode, $sCurrency, $dGrandTotal, $blGetConfigWithoutTotals);
    }

    /**
     * Returns matching Ratepay shop config by quote
     *
     * @param  string $sMethodCode
     * @param  \Magento\Quote\Api\Data\CartInterface|null $quote
     * @param  bool $blGetConfigWithoutTotals
     * @return array
     */
    public function getShopConfigByQuote($sMethodCode, \Magento\Quote\Api\Data\CartInterface $quote = null, $blGetConfigWithoutTotals = false)
    {
        if ($quote === null) {
            $quote = $this->checkoutSession->getQuote();
        }

        $sShopId = $this->getShopIdByQuote($sMethodCode, $quote, $blGetConfigWithoutTotals);
        if (empty($sShopId)) {
            return [];
        }
        return $this->getRatepayShopConfigById($sShopId);
    }

    /**
     * Returns a certain property from the given shop config array
     *
     * @param  array $aShopConfig
     * @param  string $sMethodCode
     * @param  string $sProperty
     * @return string|false
     */
    public function getShopConfigProperty($aShopConfig, $sMethodCode, $sProperty)
    {
        $sRatepayMethodIdentifier = $this->getRatepayMethodIdentifierByMethodCode($sMethodCode);

        if (isset($aShopConfig[$sProperty.'_'.$sRatepayMethodIdentifier])) {
            return $aShopConfig[$sProperty.'_'.$sRatepayMethodIdentifier];
        }
        return false;
    }

    /**
     * Get matching Ratepay shop config for current transaction
     *
     * @param string $sShopId
     * @return array|false
     */
    public function getRatepayShopConfigById($sShopId)
    {
        $aProfileConfigs = $this->profileResource->getProfileConfigsByIds([$sShopId]);
        if (!empty($aProfileConfigs)) {
            return array_shift($aProfileConfigs);
        }
        return false;
    }

    /**
     * Return Ratepay config for config provider
     *
     * @return array
     */
    public function getRatepayConfig()
    {
        $aReturn = [];

        foreach (PayoneConfig::METHODS_RATEPAY as $sRatepayMethod) {
            if ($this->paymentHelper->isPaymentMethodActive($sRatepayMethod) === true) {
                $aReturn[$sRatepayMethod] = $this->getRatepaySingleConfig($sRatepayMethod);
            }
        }
        $aReturn['snippetId'] = $this->getConfigParam('devicefingerprint_snippet_id', 'ratepay', 'payone_misc');
        $aReturn['token'] = $this->getRatepayDeviceFingerprintToken();
        return $aReturn;
    }

    /**
     * Return Ratepay configuration for given method code
     *
     * @param  string $sRatepayMethodCode
     * @param  \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return array|bool[]
     */
    public function getRatepaySingleConfig($sRatepayMethodCode, \Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $aShopConfig = $this->getShopConfigByQuote($sRatepayMethodCode, $quote);
        if (empty($aShopConfig)) {
            $aShopConfig = $this->getShopConfigByQuote($sRatepayMethodCode, $quote, true);
            if (empty($aShopConfig)) {
                return [];
            }
        }

        return [
            'b2bAllowed' => (bool)$this->getShopConfigProperty($aShopConfig, $sRatepayMethodCode, 'b2b'),
            'differentAddressAllowed' => (bool)$this->getShopConfigProperty($aShopConfig, $sRatepayMethodCode, 'delivery_address'),
        ];
    }
}
