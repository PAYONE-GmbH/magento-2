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
 * @copyright 2003 - 2025 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for cash on delivery payment method
 */
class GooglePay extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_GOOGLE_PAY;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'wlt';

    /**
     * Wallettype for PAYONE requests
     *
     * @var string|bool
     */
    protected $sWallettype = 'GGP';

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
        'payment_token',
    ];

    /**
     * Add the checkout-form-data to the checkout session
     *
     * @param  DataObject $data
     * @return $this
     */
    public function assignData(DataObject $data)
    {
        $mParentReturn = parent::assignData($data);

        $oInfoInstance = $this->getInfoInstance();
        foreach ($this->aAssignKeys as $sKey) {
            $sData = $this->toolkitHelper->getAdditionalDataEntry($data, $sKey);
            if ($sData) {
                $oInfoInstance->setAdditionalInformation($sKey, $sData);
            }
        }

        return $mParentReturn;
    }

    /**
     * Return store name from general config
     *
     * @return string|null
     */
    protected function getGeneralConfigStoreName()
    {
        $oQuote = $this->checkoutSession->getQuote();
        if (!empty($oQuote)) {
            $oStore = $oQuote->getStore();
            if (!empty($oStore)) {
                return $oStore->getFrontendName();
            }
        }
        return null;
    }

    /**
     * Return store name for Google Pay JS
     *
     * @return string
     */
    protected function getStoreName()
    {
        $sStoreName = $this->shopHelper->getConfigParam('store_name', PayoneConfig::METHOD_GOOGLE_PAY, 'payment');
        if (empty($sStoreName)) {
            $sStoreName = $this->getGeneralConfigStoreName();
        }
        if (empty($sStoreName)) {
            $sStoreName = 'Online Store'; // default
        }
        return $sStoreName;
    }

    /**
     * @return string
     */
    protected function getMerchantId()
    {
        $sMid = $this->shopHelper->getConfigParam('mid');
        $sCustomMid = $this->getCustomConfigParam('mid');
        if ($this->hasCustomConfig() && !empty($sCustomMid)) {
            $sMid = $sCustomMid;
        }
        return $sMid;
    }

    /**
     * @return array
     */
    public function getFrontendConfig()
    {
        return [
            'merchantId' => $this->getMerchantId(),
            'storeName' => $this->getStoreName(),
            'googlePayMerchantId' => $this->shopHelper->getConfigParam('google_pay_merchant_id', PayoneConfig::METHOD_GOOGLE_PAY, 'payment'),
            'operationMode' => $this->getOperationMode(),
        ];
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        return [
            'wallettype' => $this->getWallettype(),
            'api_version' => '3.11',
            'add_paydata[paymentmethod_token_data]' => base64_encode($this->getInfoInstance()->getAdditionalInformation('payment_token')),
        ];
    }
}
