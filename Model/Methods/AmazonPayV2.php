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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for Amazon Pay V2 payment method
 */
class AmazonPayV2 extends PayoneMethod
{
    const BUTTON_PUBLIC_KEY = 'AE5E5B7B2SAERURYEH6DKDAZ';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_AMAZONPAYV2;

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
    protected $sWallettype = 'AMP';

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

    /**
     * @var bool
     */
    protected $blNeedsReturnedUrl = false;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'telephone',
    ];

    /**
     * @return bool
     */
    public function isAPBPayment()
    {
        if ($this->isAmazonPayExpress() === true) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isAmazonPayExpress()
    {
        if ($this->checkoutSession->getPayoneIsAmazonPayExpressPayment() === true) {
            return true;
        }
        return false;
    }

    /**
     * Returns if the current payment process is a express payment
     *
     * @return false
     */
    public function isExpressPayment()
    {
        return $this->isAmazonPayExpress();
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getCustomConfigParam('merchant_id');
    }

    /**
     * @return string
     */
    public function getButtonColor()
    {
        return $this->getCustomConfigParam('button_color') ?? 'Gold';
    }

    /**
     * @return string
     */
    public function getButtonLanguage()
    {
        return str_replace("-", "_", $this->getCustomConfigParam('button_language') ?? 'none');
    }

    /**
     * @return bool
     */
    public function useSandbox()
    {
        if ($this->getOperationMode() == 'test') {
            return true;
        }
        return false;
    }

    /**
     * Return success url for redirect payment types
     *
     * @param  Order $oOrder
     * @return string
     */
    public function getSuccessUrl(?Order $oOrder = null)
    {
        if ($this->blNeedsReturnedUrl === true) {
            return $this->url->getUrl('payone/amazon/returned');
        }
        return parent::getSuccessUrl($oOrder);
    }

    /**
     * @param  bool $blNeedsReturnedUrl
     * @return void
     */
    public function setNeedsReturnedUrl($blNeedsReturnedUrl)
    {
        $this->blNeedsReturnedUrl = $blNeedsReturnedUrl;
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
        $aParams = ['wallettype' => $this->getWallettype()];

        $sWorkorderId = $this->checkoutSession->getPayoneWorkorderId();
        if ($sWorkorderId) {
            $aParams['workorderid'] = $sWorkorderId;
        }

        if ($this->isAPBPayment() === true) {
            $aParams['add_paydata[checkoutMode]'] = 'ProcessOrder';
            $aParams['add_paydata[productType]'] = $oOrder->getIsVirtual() ? 'PayOnly' : 'PayAndShip';

            $sTelephone = $this->getInfoInstance()->getAdditionalInformation('telephone');
            if (!empty($sTelephone)) {
                $aParams['telephonenumber'] = $sTelephone;
            }
        }
        return $aParams;
    }

    /**
     * Perform certain actions with the response
     * Extension hook for certain payment methods
     *
     * @param  array $aResponse
     * @param  Order $oOrder
     * @param  float $amount
     * @return array
     */
    protected function handleResponse($aResponse, Order $oOrder, $amount)
    {
        $aResponse = parent::handleResponse($aResponse, $oOrder, $amount);
        if ($this->isAPBPayment() === true && isset($aResponse['status'], $aResponse['add_paydata[signature]'], $aResponse['add_paydata[payload]']) && in_array($aResponse['status'], ['OK', 'REDIRECT'])) {
            $this->checkoutSession->setPayoneAmazonPaySignature($aResponse['add_paydata[signature]']);
            $this->checkoutSession->setPayoneAmazonPayPayload($aResponse['add_paydata[payload]']);
        }
        return $aResponse;
    }


    /**
     * @return array
     */
    public function getFrontendConfig()
    {
        return [
            'buttonPublicKey' => self::BUTTON_PUBLIC_KEY,
            'merchantId' => $this->getMerchantId(),
            'buttonColor' => $this->getButtonColor(),
            'buttonLanguage' => $this->getButtonLanguage(),
            'useSandbox' => $this->useSandbox(),
        ];
    }
}
