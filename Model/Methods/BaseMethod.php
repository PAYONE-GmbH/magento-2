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

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Abstract model for all the PAYONE payment methods
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
abstract class BaseMethod extends AbstractMethod
{
    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\Basic';

    /**
     * Form block path
     *
     * @var string
     */
    protected $_formBlockType = 'Payone\Core\Block\Form\Base';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Determines if payment type can use refund mechanism
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Determines if payment type can use capture mechanism
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Determines if payment type can use partial captures
     * Is true for all PAYONE Payment Methods
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Determines if payment type can use partial refunds
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string|bool
     */
    protected $sClearingtype = false;

    /**
     * Wallettype for PAYONE requests
     *
     * @var string|bool
     */
    protected $sWallettype = false;

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = false;

    /**
     * Determines if the transaction_param-parameter has to be added to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsTransactionParam = false;

    /**
     * Determines if the invoice information has to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsProductInfo = false;

    /**
     * Determines if the bank data has to be added to the debit-request
     *
     * @var bool
     */
    protected $blNeedsSepaDataOnDebit = false;

    /**
     * Max length for narrative text parameter
     *
     * @var int
     */
    protected $iNarrativeTextMax = 81;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * PAYONE api helper
     *
     * @var \Payone\Core\Helper\Api
     */
    protected $apiHelper;

    /**
     * URL helper
     *
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * Checkout session model
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Payment method group identifier
     *
     * @var string|bool
     */
    protected $sGroupName = false;

    /**
     * Payment method sub type
     *
     * @var string|bool
     */
    protected $sSubType = false;

    /**
     * PAYONE debit request model
     *
     * @var \Payone\Core\Model\Api\Request\Debit
     */
    protected $debitRequest;

    /**
     * PAYONE capture request model
     *
     * @var \Payone\Core\Model\Api\Request\Capture
     */
    protected $captureRequest;

    /**
     * PAYONE authorization request model
     *
     * @var \Payone\Core\Model\Api\Request\Authorization
     */
    protected $authorizationRequest;

    /**
     * Resource model for saved payment data
     *
     * @var \Payone\Core\Model\ResourceModel\SavedPaymentData
     */
    protected $savedPaymentData;

    /**
     * If not empty, the payment method will only be shown if one of the allowed currencies is active in checkout
     *
     * @var array
     */
    protected $aAllowedCurrencies = [];

    /**
     * Available countries for current payment method
     *
     * @var string[]
     */
    protected $aAvailableCountries = [];

    /**
     * Determines if B2B orders are not allowed for this payment method
     * B2B is assumed when the company field in the billing address is filled
     *
     * @var bool
     */
    protected $blIsB2BNotAllowed = false;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory       $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory            $customAttrFactory
     * @param \Magento\Payment\Helper\Data                            $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                    $logger
     * @param \Payone\Core\Helper\Toolkit                             $toolkitHelper
     * @param \Payone\Core\Helper\Shop                                $shopHelper
     * @param \Payone\Core\Helper\Api                                 $apiHelper
     * @param \Magento\Framework\Url                                  $url
     * @param \Magento\Checkout\Model\Session                         $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Debit                    $debitRequest
     * @param \Payone\Core\Model\Api\Request\Capture                  $captureRequest
     * @param \Payone\Core\Model\Api\Request\Authorization            $authorizationRequest
     * @param \Payone\Core\Model\ResourceModel\SavedPaymentData       $savedPaymentData
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttrFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Magento\Framework\Url $url,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Debit $debitRequest,
        \Payone\Core\Model\Api\Request\Capture $captureRequest,
        \Payone\Core\Model\Api\Request\Authorization $authorizationRequest,
        \Payone\Core\Model\ResourceModel\SavedPaymentData $savedPaymentData,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttrFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);
        $this->toolkitHelper = $toolkitHelper;
        $this->shopHelper = $shopHelper;
        $this->apiHelper = $apiHelper;
        $this->url = $url;
        $this->checkoutSession = $checkoutSession;
        $this->debitRequest = $debitRequest;
        $this->captureRequest = $captureRequest;
        $this->authorizationRequest = $authorizationRequest;
        $this->savedPaymentData = $savedPaymentData;
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions') ?? ''); // return description text
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return AbstractMethod::ACTION_AUTHORIZE; // only create order
    }

    /**
     * Authorize payment abstract method
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return AbstractMethod
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        $oReturn = parent::authorize($payment, $amount); // execute Magento parent authorization
        if (!$this->_registry->registry('payone_creating_substitute_order')) {
            $this->sendPayoneAuthorization($payment, $amount); // send auth request to PAYONE
        } else {
            $payment->getOrder()->setCanSendNewEmailFlag(false); // dont send email now, will be sent on appointed
        }
        return $oReturn; // return magento parent auth value
    }

    /**
     * Refund payment abstract method
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return AbstractMethod
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $oReturn = parent::refund($payment, $amount); // execute Magento parent refund
        $this->sendPayoneDebit($payment, $amount); // send debit request to PAYONE
        return $oReturn; // return magento parent refund value
    }

    /**
     * Capture payment abstract method
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return AbstractMethod
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $oReturn = parent::capture($payment, $amount); // execute Magento parent capture
        if ($payment->getParentTransactionId()) {// does the order already have a transaction?
            $this->sendPayoneCapture($payment, $amount); // is probably admin invoice capture
        } else {
            $this->sendPayoneAuthorization($payment, $amount); // is probably frontend checkout capture
        }
        return $oReturn; // return magento parent capture value
    }

    /**
     * To check billing country is allowed for the payment method
     * Overrides the parent method with extended behaviour
     *
     * @param  string $country
     * @return bool
     */
    public function canUseForCountry($country)
    {
        if (!empty($this->aAvailableCountries)) { // payment method has specific countries specified?
            if (in_array($country, $this->aAvailableCountries) === false) {
                return false;
            }
            return true;
        }

        $aAvailableCountries = [];

        $iAllowSpecific = $this->shopHelper->getConfigParam('allowspecific');
        $sSpecificCountry = $this->shopHelper->getConfigParam('specificcountry');
        if ($this->hasCustomConfig()) {// check for non-global configuration
            $iAllowSpecific = $this->getCustomConfigParam('allowspecific'); // only specific countries allowed?
            $sSpecificCountry = $this->getCustomConfigParam('specificcountry');
        }

        if (!empty($sSpecificCountry)) {
            $aAvailableCountries = explode(',', $sSpecificCountry);
        }
        if ($iAllowSpecific == 1 && !in_array($country, $aAvailableCountries)) {// only specific but not included
            return false; // cant use for given country
        }
        return true; // can use for given country
    }

    /**
     * Returns if the current payment process is a express payment
     *
     * @return false
     */
    public function isExpressPayment()
    {
        return false;
    }
}
