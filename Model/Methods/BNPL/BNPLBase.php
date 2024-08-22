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
 * @copyright 2003 - 2023 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods\BNPL;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Framework\DataObject;

/**
 * Base class for all BNPL methods
 */
class BNPLBase extends PayoneMethod
{
    /* Payment method sub types */
    const METHOD_BNPL_SUBTYPE_INVOICE = 'PIV';
    const METHOD_BNPL_SUBTYPE_INSTALLMENT = 'PIN';
    const METHOD_BNPL_SUBTYPE_DEBIT = 'PDD';

    const BNPL_PARTNER_ID = 'e7yeryF2of8X';

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'fnc';

    /**
     * Payment method group identifier
     *
     * @var string
     */
    protected $sGroupName = PayoneConfig::METHOD_GROUP_BNPL;

    /**
     * Payment method long sub type
     *
     * @var string|bool
     */
    protected $sLongSubType = false;

    /**
     * Determines if the invoice information has to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsProductInfo = true;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'dateofbirth',
        'telephone',
        'iban',
        'installmentOption',
        'optionid',
        'vatid',
    ];

    /**
     * Available countries for BNPL payment usage
     *
     * @var string[]
     */
    protected $aAvailableCountries = [
        'DE',
        'AT',
    ];

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\ClearingReference';

    /**
     * @var \Payone\Core\Helper\Api
     */
    protected $apiHelper;

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
     * @param \Magento\Framework\Url                                  $url
     * @param \Magento\Checkout\Model\Session                         $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Debit                    $debitRequest
     * @param \Payone\Core\Model\Api\Request\Capture                  $captureRequest
     * @param \Payone\Core\Model\Api\Request\Authorization            $authorizationRequest
     * @param \Payone\Core\Model\ResourceModel\SavedPaymentData       $savedPaymentData
     * @param \Payone\Core\Helper\Api                                 $apiHelper
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
        \Magento\Framework\Url $url,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Debit $debitRequest,
        \Payone\Core\Model\Api\Request\Capture $captureRequest,
        \Payone\Core\Model\Api\Request\Authorization $authorizationRequest,
        \Payone\Core\Model\ResourceModel\SavedPaymentData $savedPaymentData,
        \Payone\Core\Helper\Api $apiHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttrFactory, $paymentData, $scopeConfig, $logger, $toolkitHelper, $shopHelper, $url, $checkoutSession, $debitRequest, $captureRequest, $authorizationRequest, $savedPaymentData, $resource, $resourceCollection, $data);
        $this->apiHelper = $apiHelper;
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $blParentReturn = parent::isAvailable($quote);

        // BNPL payment types are only available for payment in Euro
        if ($quote !== null && $this->apiHelper->getCurrencyFromQuote($quote) != "EUR") {
            return false;
        }
        return $blParentReturn;
    }

    /**
     * To check billing country is allowed for the payment method
     *
     * @param string $country
     * @return bool
     * @deprecated 100.2.0
     */
    public function canUseForCountry($country)
    {
        // BNPL payments are only available for DE and AT orders
        if (in_array($country, $this->aAvailableCountries) === false) {
            return false;
        }
        return true;
    }

    /**
     * Returns device token
     *
     * @return string
     */
    protected function getDeviceToken()
    {
        $sUuid = $this->checkoutSession->getPayoneUUID();
        $sMid = $this->shopHelper->getConfigParam('mid');
        $sCustomMid = $this->getCustomConfigParam('mid');
        if ($this->hasCustomConfig() && !empty($sCustomMid)) {
            $sMid = $sCustomMid;
        }
        return self::BNPL_PARTNER_ID.'_'.$sMid.'_'.$sUuid;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $oInfoInstance = $this->getInfoInstance();

        $sBusinessrelation = 'b2c';
        if (!empty($oOrder->getBillingAddress()->getCompany())) {
            $sBusinessrelation = 'b2b';
        }

        $aBaseParams = [
            'financingtype' => $this->getSubType(),
            'add_paydata[device_token]' => $this->getDeviceToken(),
            'businessrelation' => $sBusinessrelation,
            'birthday' => $oInfoInstance->getAdditionalInformation('dateofbirth')
        ];

        $sTelephone = $oInfoInstance->getAdditionalInformation('telephone');
        if (!empty($sTelephone)) {
            $aBaseParams['telephonenumber'] = $sTelephone;
        }

        $sVatId = $oInfoInstance->getAdditionalInformation('vatid');
        if ($sBusinessrelation == 'b2b' && !empty($sVatId)) {
            $aBaseParams['vatid'] = $sVatId;
        }

        $aSubTypeParams = $this->getSubTypeSpecificParameters($oOrder);
        $aParams = array_merge($aBaseParams, $aSubTypeParams);
        return $aParams;
    }

    /**
     * Add the checkout-form-data to the checkout session
     *
     * @param  DataObject $data
     * @return $this
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $oInfoInstance = $this->getInfoInstance();
        foreach ($this->aAssignKeys as $sKey) {
            $sData = $this->toolkitHelper->getAdditionalDataEntry($data, $sKey);
            if ($sData) {
                $oInfoInstance->setAdditionalInformation($sKey, $sData);
            }
        }

        return $this;
    }

    /**
     * Perform certain actions with the response
     *
     * @param  array $aResponse
     * @param  Order $oOrder
     * @param  float $amount
     * @return array
     */
    protected function handleResponse($aResponse, Order $oOrder, $amount)
    {
        $aResponse = parent::handleResponse($aResponse, $oOrder, $amount);
        if (isset($aResponse['status']) && $aResponse['status'] == 'ERROR' && isset($aResponse['errorcode']) && $aResponse['errorcode'] == '307') {
            $aBans = $this->checkoutSession->getPayonePaymentBans();
            if (empty($aBans)) {
                $aBans = [];
            }
            
            $sBannedUntil = date('Y-m-d H:i:s', (time() + (60 * 60 * 8)));
            $aBans[PayoneConfig::METHOD_BNPL_DEBIT] = $sBannedUntil;
            $aBans[PayoneConfig::METHOD_BNPL_INSTALLMENT] = $sBannedUntil;
            $aBans[PayoneConfig::METHOD_BNPL_INVOICE] = $sBannedUntil;
            $this->checkoutSession->setPayonePaymentBans($aBans);
        }
        return $aResponse;
    }
}
