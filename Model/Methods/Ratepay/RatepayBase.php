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

namespace Payone\Core\Model\Methods\Ratepay;

use Payone\Core\Model\Methods\Payolution\PayolutionBase;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Base class for all Ratepay methods
 */
class RatepayBase extends PayoneMethod
{
    /* Payment method sub types */
    const METHOD_RATEPAY_SUBTYPE_INVOICE = 'RPV';
    const METHOD_RATEPAY_SUBTYPE_INSTALLMENT = 'RPS';
    const METHOD_RATEPAY_SUBTYPE_DEBIT = 'RPD';

    /**
     * Payone Ratepay helper
     *
     * @var \Payone\Core\Helper\Ratepay
     */
    protected $ratepayHelper;

    /**
     * Payone Api helper
     *
     * @var \Payone\Core\Helper\Api
     */
    protected $apiHelper;

    /**
     * Payment ban resource model
     *
     * @var \Payone\Core\Model\ResourceModel\PaymentBan
     */
    protected $paymentBan;

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
    protected $sGroupName = PayoneConfig::METHOD_GROUP_RATEPAY;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\ClearingReference';

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'telephone',
        'dateofbirth',
        'birthday',
        'birthmonth',
        'birthyear',
        'iban',
        'bic',
        'company_uid',
    ];

    /**
     * Payment ban duration in hours
     *
     * @var int
     */
    protected $iBanDuration = 48;

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
     * @param \Payone\Core\Helper\Ratepay                             $ratepayHelper
     * @param \Payone\Core\Helper\Api                                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\PaymentBan             $paymentBan
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
        \Payone\Core\Helper\Ratepay $ratepayHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\PaymentBan $paymentBan,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttrFactory, $paymentData, $scopeConfig, $logger, $toolkitHelper, $shopHelper, $url, $checkoutSession, $debitRequest, $captureRequest, $authorizationRequest, $savedPaymentData, $resource, $resourceCollection, $data);
        $this->ratepayHelper = $ratepayHelper;
        $this->apiHelper = $apiHelper;
        $this->paymentBan = $paymentBan;
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
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aBaseParams = [
            'financingtype' => $this->getSubType(),
            'api_version' => '3.10',
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[device_token]' => $this->ratepayHelper->getRatepayDeviceFingerprintToken(),
            'add_paydata[merchant_consumer_id]' => $oOrder->getCustomerId(),
            'add_paydata[shop_id]' => $this->getShopIdByOrder($oOrder),
        ];

        $sCompanyUid = $this->getInfoInstance()->getAdditionalInformation('company_uid');
        if (!empty($sCompanyUid)) {
            $aBaseParams['add_paydata[vat_id]'] = $sCompanyUid;
        }

        $sBirthday = $this->getInfoInstance()->getAdditionalInformation('dateofbirth');
        if ($sBirthday) {
            $aBaseParams['birthday'] = $sBirthday;
        } elseif (!empty($this->getInfoInstance()->getAdditionalInformation('birthday'))) {
            $sDayOfBirth = $this->getInfoInstance()->getAdditionalInformation('birthday');
            $sMonthOfBirth = $this->getInfoInstance()->getAdditionalInformation('birthmonth');
            $sYearOfBirth = $this->getInfoInstance()->getAdditionalInformation('birthyear');
            $aBaseParams['birthday'] = $sYearOfBirth.$sMonthOfBirth.$sDayOfBirth;
        }

        $sTelephone = $this->getInfoInstance()->getAdditionalInformation('telephone');
        if ($sTelephone) {
            $aBaseParams['telephonenumber'] = $sTelephone;
        }

        $aSubTypeParams = $this->getSubTypeSpecificParameters($oOrder);
        $aParams = array_merge($aBaseParams, $aSubTypeParams);
        return $aParams;
    }

    /**
     * Returns matching Ratepay shop id by given quote
     *
     * @param Order $oOrder
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getShopIdByOrder(Order $oOrder)
    {
        $sCountryCode = $oOrder->getShippingAddress()->getCountryId();
        $sCurrency = $this->apiHelper->getCurrencyFromOrder($oOrder);
        $dGrandTotal = $this->apiHelper->getOrderAmount($oOrder);

        return $this->ratepayHelper->getRatepayShopId($this->getCode(), $sCountryCode, $sCurrency, $dGrandTotal);
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $blParentReturn = parent::isAvailable($quote);
        if ($blParentReturn === false) {
            return false;
        }

        if ($quote === null) {
            $quote = $this->checkoutSession->getQuote();
        }

        if ($quote !== null && $this->ratepayHelper->getShopIdByQuote($this->getCode(), $quote) === false) {
            return false;
        }

        return $blParentReturn;
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
        $this->checkoutSession->unsPayoneRatepayDeviceFingerprintToken();
        if (isset($aResponse['status']) && $aResponse['status'] == 'ERROR'
            && isset($aResponse['errorcode']) && $aResponse['errorcode'] == '307'
        ) {
            if (!empty($oOrder->getCustomerId())) {
                $this->paymentBan->addPaymentBan($this->getCode(), $oOrder->getCustomerId(), $this->iBanDuration);
            } else { // guest checkout
                $aBans = $this->checkoutSession->getPayonePaymentBans();
                if (!$aBans) {
                    $aBans = [];
                }
                $aBans[$this->getCode()] = $this->paymentBan->getBanEndDate($this->iBanDuration);
                $this->checkoutSession->setPayonePaymentBans($aBans);
            }
        }
        return $aResponse;
    }

    /**
     * Return capture parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificCaptureParameters(Order $oOrder)
    {
        return [
            'add_paydata[shop_id]' => $oOrder->getPayoneRatepayShopId()
        ];
    }

    /**
     * Return debit parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificDebitParameters(Order $oOrder)
    {
        return [
            'add_paydata[shop_id]' => $oOrder->getPayoneRatepayShopId()
        ];
    }
}
