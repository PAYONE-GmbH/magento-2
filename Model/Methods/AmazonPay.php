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

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;

/**
 * Model for Amazon Pay payment method
 */
class AmazonPay extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_AMAZONPAY;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'wlt';

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

    /**
     * Flags if Amazon Pay authorization is in retry mode
     *
     * @var bool
     */
    protected $blIsRetry = false;

    /**
     * Returns authorization-mode
     * Barzahlen only supports preauthorization
     *
     * @return string
     */
    public function getAuthorizationMode()
    {
        return PayoneConfig::REQUEST_TYPE_PREAUTHORIZATION;
    }

    /**
     * Get amazon mode from config
     *
     * @return string
     */
    protected function getAmazonMode()
    {
        return $this->shopHelper->getConfigParam('amazon_mode', $this->getCode(), 'payone_payment');
    }

    /**
     * Adds amazon timeout parameters
     *
     * @param  array $aParams
     * @return array
     */
    protected function addAmazonModeParameters($aParams)
    {
        $sAmazonMode = $this->getAmazonMode();
        switch ($sAmazonMode) {
            case 'synchronousFirst':
                if ($this->checkoutSession->getAmazonRetryAsync() === true) {
                    $aParams['add_paydata[amazon_timeout]'] = 1440;
                } else {
                    $this->checkoutSession->setAmazonRetryAsync(true);
                    $aParams['add_paydata[amazon_timeout]'] = 0;
                }
                break;
            case 'asynchronous':
                $aParams['add_paydata[amazon_timeout]'] = 1440;
                break;
            case 'synchronous':
                $aParams['add_paydata[amazon_timeout]'] = 0;
                $aParams['add_paydata[cancel_on_timeout]'] = 'yes';
                break;
        }
        return $aParams;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aParams = ['wallettype' => 'AMZ'];
        $aParams['api_version'] = '3.10';

        $sWorkorderId = $this->checkoutSession->getAmazonWorkorderId();
        if ($sWorkorderId) {
            $aParams['workorderid'] = $sWorkorderId;
        }
        $aParams['add_paydata[amazon_address_token]'] = $this->checkoutSession->getAmazonAddressToken();
        $aParams['add_paydata[amazon_reference_id]'] = $this->checkoutSession->getAmazonReferenceId();
        $aParams = $this->addAmazonModeParameters($aParams);
        return $aParams;
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
        if ($aResponse['status'] == 'ERROR') {
            if (!$this->blIsRetry && $this->checkoutSession->getAmazonRetryAsync() && $aResponse['errorcode'] == 980) {
                $aResponse = $this->authorizationRequest->sendRequest($this, $oOrder, $amount);
                $this->checkoutSession->setShowAmazonPendingNotice(true);
            }
        }
        return $aResponse;
    }
}
