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
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for Apple Pay payment method
 */
class ApplePay extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_APPLEPAY;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'wlt';

    protected $applePayHelper;

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
     * @param \Payone\Core\Helper\ApplePay                            $applePayHelper
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
        \Payone\Core\Helper\ApplePay $applePayHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttrFactory, $paymentData, $scopeConfig, $logger, $toolkitHelper, $shopHelper, $url, $checkoutSession, $debitRequest, $captureRequest, $authorizationRequest, $savedPaymentData, $resource, $resourceCollection, $data);
        $this->applePayHelper = $applePayHelper;
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
        if ($this->applePayHelper->isConfigurationComplete() === false) {
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
        $oInfoInstance = $this->getInfoInstance();

        $sJsonToken = $oInfoInstance->getAdditionalInformation('token');
        $aToken = json_decode($sJsonToken, true);
        if (empty($aToken) || !is_array($aToken)) {
            throw new \Exception("Apple Pay token is missing!");
        }

        $aParams = [
            'wallettype' => 'APL',
            'api_version' => '3.11',
            'add_paydata[paymentdata_token_data]'                   => $aToken['paymentData']['data'],
            'add_paydata[paymentdata_token_signature]'              => $aToken['paymentData']['signature'],
            'add_paydata[paymentdata_token_version]'                => $aToken['paymentData']['version'],
            'add_paydata[paymentdata_token_ephemeral_publickey]'    => $aToken['paymentData']['header']['ephemeralPublicKey'],
            'add_paydata[paymentdata_token_publickey_hash]'         => $aToken['paymentData']['header']['publicKeyHash'],
            'add_paydata[paymentdata_token_transaction_id]'         => $aToken['paymentData']['header']['transactionId'],
        ];
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
        $oInfoInstance->setAdditionalInformation('token', $this->toolkitHelper->getAdditionalDataEntry($data, 'token'));

        return $this;
    }
}
