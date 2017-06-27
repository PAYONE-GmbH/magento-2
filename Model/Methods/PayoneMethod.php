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

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;

/**
 * Abstract model for all the PAYONE payment methods
 */
abstract class PayoneMethod extends BaseMethod
{
    /**
     * Returns clearingtype
     *
     * @return string
     * @throws LocalizedException
     */
    public function getClearingtype()
    {
        return $this->sClearingtype;
    }

    /**
     * Returns authorization-mode
     * preauthorization or authorization
     *
     * @return string
     */
    public function getAuthorizationMode()
    {
        $sRequestType = $this->shopHelper->getConfigParam('request_type');
        if ($this->hasCustomConfig()) {
            $sRequestType = $this->getCustomConfigParam('request_type');
        }
        return $sRequestType;
    }

    /**
     * Method handling the debit request and the response
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return void
     * @throws LocalizedException
     */
    protected function sendPayoneDebit(InfoInterface $payment, $amount)
    {
        $aResponse = $this->debitRequest->sendRequest($this, $payment, $amount);
        if ($aResponse['status'] == 'ERROR') {
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
        } elseif (!$aResponse) {
            throw new LocalizedException(__('Unkown error'));
        }
    }

    /**
     * Method handling the capture request and the response
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return void
     * @throws LocalizedException
     */
    protected function sendPayoneCapture(InfoInterface $payment, $amount)
    {
        $aResponse = $this->captureRequest->sendRequest($this, $payment, $amount);
        if ($aResponse['status'] == 'ERROR') {// request returned an error
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
        } elseif (!$aResponse) {// response not existing
            throw new LocalizedException(__('Unkown error'));
        }
    }

    /**
     * Method handling the authorization request and the response
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return void
     * @throws LocalizedException
     */
    protected function sendPayoneAuthorization(InfoInterface $payment, $amount)
    {
        $oOrder = $payment->getOrder();
        $oOrder->setCanSendNewEmailFlag(false); // dont send email now, will be sent on appointed
        $this->checkoutSession->unsPayoneRedirectUrl(); // remove redirect url from session
        $aResponse = $this->authorizationRequest->sendRequest($this, $oOrder, $amount);
        $this->handleResponse($aResponse);
        if ($aResponse['status'] == 'ERROR') {// request returned an error
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
        } elseif ($aResponse['status'] == 'APPROVED' || $aResponse['status'] == 'REDIRECT') {// request successful
            $payment->setTransactionId($aResponse['txid']);
            $payment->setIsTransactionClosed(0);
            if ($aResponse['status'] == 'REDIRECT') {// user needs to be redirected to external payment page
                $this->checkoutSession->setPayoneRedirectUrl($aResponse['redirecturl']);
            }
        }
    }

    /**
     * Perform certain actions with the response
     *
     * @param  array $aResponse
     * @return void
     */
    protected function handleResponse($aResponse)
    {
        // hook for certain payment methods
    }

    /**
     * Returns operationmode live or test for this payment method
     *
     * @return string
     */
    public function getOperationMode()
    {
        return $this->getCustomConfigParam('mode');
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        return []; // filled in child classes
    }

    /**
     * Return success url for redirect payment types
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->url->getUrl('payone/onepage/returned');
    }

    /**
     * Return cancel url for redirect payment types
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->url->getUrl('payone/onepage/cancel');
    }

    /**
     * Return error url for redirect payment types
     *
     * @return string
     */
    public function getErrorUrl()
    {
        return $this->url->getUrl('payone/onepage/cancel?error=1');
    }

    /**
     * Return if redirect urls have to be added to the authroization request
     *
     * @return bool
     */
    public function needsRedirectUrls()
    {
        return $this->blNeedsRedirectUrls;
    }

    /**
     * Return if invoice data has to be added to the authroization request
     *
     * @return bool
     */
    public function needsProductInfo()
    {
        return $this->blNeedsProductInfo;
    }

    /**
     * Get config parameter for this payment type
     *
     * @param  string $sParam
     * @return string
     */
    public function getCustomConfigParam($sParam)
    {
        return $this->shopHelper->getConfigParam($sParam, $this->getCode(), 'payone_payment');
    }

    /**
     * Returns if global PAYONE config is used for this payment type
     *
     * @return bool
     */
    public function hasCustomConfig()
    {
        if ($this->getCustomConfigParam('use_global') == '0') {// has non-global config
            return true;
        }
        return false;
    }

    /**
     * Return if this payment method is part of a group
     *
     * @return bool
     */
    public function isGroupMethod()
    {
        if ($this->sGroupName === false) {
            return false;
        }
        return true;
    }

    /**
     * Returns group identifier
     *
     * @return string|bool
     */
    public function getGroupName()
    {
        return $this->sGroupName;
    }

    /**
     * Returns group identifier
     *
     * @return string|bool
     */
    public function getSubType()
    {
        return $this->sSubType;
    }

    /**
     * Return parameters specific to this payment sub type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getSubTypeSpecificParameters(Order $oOrder)
    {
        return []; // filled in child classes
    }

    /**
     * Formats the reference number if needed for this payment method
     * Needed for Paydirekt
     *
     * @param  string $sRefNr
     * @return string
     */
    public function formatReferenceNumber($sRefNr)
    {
        return $sRefNr;
    }

    /**
     * Return max length of narrative text
     *
     * @return int
     */
    public function getNarrativeTextMaxLength()
    {
        return $this->iNarrativeTextMax;
    }
}
