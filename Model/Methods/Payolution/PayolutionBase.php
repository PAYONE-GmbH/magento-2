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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods\Payolution;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Framework\DataObject;

/**
 * Base class for all Payolution methods
 */
class PayolutionBase extends PayoneMethod
{
    /* Payment method sub types */
    const METHOD_PAYOLUTION_SUBTYPE_INVOICE = 'PYV';
    const METHOD_PAYOLUTION_SUBTYPE_DEBIT = 'PYD';
    const METHOD_PAYOLUTION_SUBTYPE_INSTALLMENT = 'PYS';

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
    protected $sGroupName = PayoneConfig::METHOD_GROUP_PAYOLUTION;

    /**
     * Payment method long sub type
     *
     * @var string|bool
     */
    protected $sLongSubType = false;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'telephone',
        'b2bmode',
        'trade_registry_number',
        'dateofbirth'
    ];

    /**
     * PAYONE genericpayment pre_check request model
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\PreCheck
     */
    protected $precheckRequest;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\ClearingReference';

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
     * @param \Payone\Core\Model\Api\Request\Genericpayment\PreCheck  $precheckRequest
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
        \Payone\Core\Model\Api\Request\Genericpayment\PreCheck $precheckRequest,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttrFactory, $paymentData, $scopeConfig, $logger, $toolkitHelper, $shopHelper, $url, $checkoutSession, $debitRequest, $captureRequest, $authorizationRequest, $resource, $resourceCollection, $data);
        $this->precheckRequest = $precheckRequest;
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
            'workorderid' => $this->getInfoInstance()->getAdditionalInformation('workorderid'),
        ];

        $sBirthday = $this->getInfoInstance()->getAdditionalInformation('dateofbirth');
        if ($sBirthday) {
            $aBaseParams['birthday'] = $sBirthday;
        }

        $blB2b = $this->getInfoInstance()->getAdditionalInformation('b2bmode');
        if ($blB2b == '1') {
            $aBaseParams['add_paydata[b2b]'] = 'yes';
            $aBaseParams['add_paydata[company_trade_registry_number]'] = $this->getInfoInstance()->getAdditionalInformation('trade_registry_number');
        }

        $aSubTypeParams = $this->getSubTypeSpecificParameters($oOrder);
        $aParams = array_merge($aBaseParams, $aSubTypeParams);
        return $aParams;
    }

    /**
     * Method to trigger the Payone genericpayment request pre-check
     *
     * @param  float $dAmount
     * @return array
     * @throws LocalizedException
     */
    public function sendPayonePreCheck($dAmount)
    {
        $oQuote = $this->checkoutSession->getQuote();
        if ($this->shopHelper->getConfigParam('currency') == 'display') {
            $dAmount = $oQuote->getGrandTotal(); // send display amount instead of base amount
        }
        $aResponse = $this->precheckRequest->sendRequest($this, $oQuote, $dAmount);

        if ($aResponse['status'] == 'ERROR') {// request returned an error
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
        } elseif ($aResponse['status'] == 'OK') {
            $this->getInfoInstance()->setAdditionalInformation('workorderid', $aResponse['workorderid']);
        }
        return $aResponse;
    }

    /**
     * Return long subtype
     *
     * @return string
     */
    public function getLongSubType()
    {
        return $this->sLongSubType;
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
}
