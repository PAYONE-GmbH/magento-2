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
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for creditcard payment method
 */
class Creditcard extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_CREDITCARD;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\Creditcard';

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'cc';

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'pseudocardpan',
        'truncatedcardpan',
        'cardtype',
        'cardexpiredate',
        'selectedData',
        'cardholder',

    ];

    /**
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

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
     * @param \Payone\Core\Helper\Payment                             $paymentHelper
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
        \Payone\Core\Helper\Payment $paymentHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttrFactory, $paymentData, $scopeConfig, $logger, $toolkitHelper, $shopHelper, $url, $checkoutSession, $debitRequest, $captureRequest, $authorizationRequest, $savedPaymentData, $resource, $resourceCollection, $data);
        $this->paymentHelper = $paymentHelper;
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
        if ($blParentReturn === false) {
            return false;
        }

        // dont show payment method when configuration is not complete
        if (empty($this->paymentHelper->getAvailableCreditcardTypes())) {
            return false;
        }
        return $blParentReturn;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aReturn = ['pseudocardpan' => $this->getInfoInstance()->getAdditionalInformation('pseudocardpan')];
        $sSelectedData = $this->getInfoInstance()->getAdditionalInformation('selectedData');
        if (!empty($sSelectedData) && $sSelectedData != 'new') {
            $aReturn['pseudocardpan'] = $this->getInfoInstance()->getAdditionalInformation('selectedData');
        }
        $aReturn['cardholder'] = $this->getInfoInstance()->getAdditionalInformation('cardholder');
        return $aReturn;
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

        $aAddData = $data->getAdditionalData();
        if (isset($aAddData['saveData']) && $aAddData['saveData'] == '1') {
            $this->handlePaymentDataStorage($data);
        }

        return $this;
    }

    /**
     * Add value to the payment storage data array
     *
     * @param  array  $aDest
     * @param  array  $aSource
     * @param  string $sDestField
     * @param  string $sSourceField
     * @return void
     */
    protected function addValueToArray(&$aDest, $aSource, $sDestField, $sSourceField)
    {
        if (isset($aSource[$sSourceField])) {
            $aDest[$sDestField] = $aSource[$sSourceField];
        }
    }

    /**
     * Convert DataObject to needed array format
     *
     * @param  DataObject $data
     * @return array
     */
    protected function getPaymentStorageData(DataObject $data)
    {
        $aReturn = parent::getPaymentStorageData($data);
        $aAdditionalData = $data->getAdditionalData();

        if (isset($aAdditionalData['pseudocardpan']) && isset($aAdditionalData['truncatedcardpan'])) {
            $this->addValueToArray($aReturn, $aAdditionalData, 'cardpan', 'pseudocardpan');
            $this->addValueToArray($aReturn, $aAdditionalData, 'masked', 'truncatedcardpan');
            $this->addValueToArray($aReturn, $aAdditionalData, 'cardholder', 'cardholder');
            $this->addValueToArray($aReturn, $aAdditionalData, 'cardtype', 'cardtype');
            $this->addValueToArray($aReturn, $aAdditionalData, 'cardexpiredate', 'cardexpiredate');
        }
        return $aReturn;
    }
}
