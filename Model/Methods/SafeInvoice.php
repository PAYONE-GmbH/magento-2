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
 * Model for safe invoice payment method
 */
class SafeInvoice extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_SAFE_INVOICE;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'rec';

    /**
     * Determines if the invoice information has to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsProductInfo = true;

    /**
     * Payment ban entity
     *
     * @var \Payone\Core\Model\ResourceModel\PaymentBan
     */
    protected $paymentBan;

    /**
     * Payment ban duration in hours
     *
     * @var int
     */
    protected $iBanDuration = 24;

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
        \Payone\Core\Model\ResourceModel\PaymentBan $paymentBan,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttrFactory, $paymentData, $scopeConfig, $logger, $toolkitHelper, $shopHelper, $url, $checkoutSession, $debitRequest, $captureRequest, $authorizationRequest, $resource, $resourceCollection, $data);
        $this->paymentBan = $paymentBan;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aParams = ['clearingsubtype' => 'POV'];

        $sDob = $this->getInfoInstance()->getAdditionalInformation('dob');
        if ($sDob) {
            $aParams['birthday'] = $sDob;
        }

        $sBusinessrelation = 'b2c';
        if (!empty($oOrder->getBillingAddress()->getCompany())) {
            $sBusinessrelation = 'b2b';
        }
        $aParams['businessrelation'] = $sBusinessrelation;

        return $aParams;
    }

    /**
     * Returns formatted birthday if possible
     *
     * @param  DataObject $data
     * @return string|false
     */
    protected function getFormattedBirthday(DataObject $data)
    {
        $sFormattedDob = false;

        $sBirthday = $this->toolkitHelper->getAdditionalDataEntry($data, 'birthday');
        $sBirthmonth = $this->toolkitHelper->getAdditionalDataEntry($data, 'birthmonth');
        $sBirthyear = $this->toolkitHelper->getAdditionalDataEntry($data, 'birthyear');
        if ($sBirthday && $sBirthmonth && $sBirthyear) {
            $sDob = $sBirthyear.'-'.$sBirthmonth.'-'.$sBirthday;
            $iDobTime = strtotime($sDob);
            if ($iDobTime !== false) {
                $sFormattedDob = date('Ymd', $iDobTime);
            }
        }
        return $sFormattedDob;
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

        $sFormattedDob = $this->getFormattedBirthday($data);
        if ($sFormattedDob !== false) {
            $oInfoInstance = $this->getInfoInstance();
            $oInfoInstance->setAdditionalInformation('dob', $sFormattedDob);
        }

        return $this;
    }

    /**
     * Perform certain actions with the response
     *
     * @param  array $aResponse
     * @param  Order $oOrder
     * @return void
     */
    protected function handleResponse($aResponse, Order $oOrder)
    {
        if (isset($aResponse['status']) && $aResponse['status'] == 'ERROR'
            && isset($aResponse['errorcode']) && $aResponse['errorcode'] == '351'
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
    }
}
