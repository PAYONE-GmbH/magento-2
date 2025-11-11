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
use Payone\Core\Model\Exception\AuthorizationException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Abstract model for all the PAYONE payment methods
 */
abstract class PayoneMethod extends BaseMethod
{
    /**
     * Returns clearingtype
     *
     * @return string
     */
    public function getClearingtype()
    {
        return $this->sClearingtype;
    }

    /**
     * Returns wallettype
     *
     * @return string
     */
    public function getWallettype()
    {
        return $this->sWallettype;
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
            $sCustomRequestType = $this->getCustomConfigParam('request_type');
            if (!empty($sCustomRequestType)) {
                $sRequestType = $sCustomRequestType;
            }
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
        if (!$aResponse) {
            throw new LocalizedException(__('Unkown error'));
        } elseif ($aResponse['status'] == 'ERROR') {
            $this->checkoutSession->setPayoneDebitRequest($this->debitRequest->getParameters());
            $this->checkoutSession->setPayoneDebitResponse($this->debitRequest->getResponse());
            $this->checkoutSession->setPayoneDebitOrderId($this->debitRequest->getOrderId());
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
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
        if (!$aResponse) {// response not existing
            throw new LocalizedException(__('Unkown error'));
        } elseif ($aResponse['status'] == 'ERROR') {// request returned an error
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
        }
    }

    /**
     * Removes status flag used during checkout process from session
     *
     * @return void
     */
    protected function unsetSessionStatusFlags() {
        $this->checkoutSession->unsPayoneRedirectUrl();
        $this->checkoutSession->unsPayoneRedirectedPaymentMethod();
        $this->checkoutSession->unsPayoneCanceledPaymentMethod();
        $this->checkoutSession->unsPayoneIsError();
        $this->checkoutSession->unsShowAmazonPendingNotice();
        $this->checkoutSession->unsAmazonRetryAsync();
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
        $this->unsetSessionStatusFlags();
        $oOrder = $payment->getOrder();
        $oOrder->setCanSendNewEmailFlag(false); // dont send email now, will be sent on appointed

        if ($this->shopHelper->getConfigParam('currency', 'global', 'payone_general', $payment->getOrder()->getStore()->getCode()) == 'display') {
            $amount = $oOrder->getTotalDue(); // send display amount instead of base amount
        }

        $this->checkoutSession->unsPayoneRedirectUrl(); // remove redirect url from session
        $this->checkoutSession->unsPayoneRedirectedPaymentMethod();
        $this->checkoutSession->unsPayoneCanceledPaymentMethod();
        $this->checkoutSession->unsPayoneIsError();

        $aResponse = $this->authorizationRequest->sendRequest($this, $oOrder, $amount);
        $aResponse = $this->handleResponse($aResponse, $oOrder, $amount);
        if ($aResponse['status'] == 'ERROR') {// request returned an error
            throw new AuthorizationException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']), $aResponse);
        } elseif ($aResponse['status'] == 'APPROVED' || $aResponse['status'] == 'REDIRECT') {// request successful
            $payment->setTransactionId($aResponse['txid']);
            $payment->setIsTransactionClosed(0);
            if ($aResponse['status'] == 'REDIRECT') {// user needs to be redirected to external payment page
                $this->checkoutSession->setPayoneRedirectUrl($aResponse['redirecturl']);
                $this->checkoutSession->setPayoneRedirectedPaymentMethod($this->getCode());
            }
        }
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
        $aAddData = $oOrder->getPayment()->getAdditionalInformation();
        if (!empty($aAddData['iban'])) {
            $oOrder->getPayment()->setAdditionalInformation('iban', $this->toolkitHelper->maskIban($aAddData['iban']));
        }
        return $aResponse;
    }

    /**
     * Convert DataObject to needed array format
     * Hook for overriding in specific payment type class
     *
     * @param  DataObject $data
     * @return array
     */
    protected function getPaymentStorageData(DataObject $data)
    {
        return [];
    }

    /**
     * Check config and save payment data
     *
     * @param  DataObject $data
     * @return void
     */
    protected function handlePaymentDataStorage(DataObject $data)
    {
        if ((bool)$this->getCustomConfigParam('save_data_enabled') === true) {
            $aPaymentData = $this->getPaymentStorageData($data);
            $iCustomerId = $this->checkoutSession->getQuote()->getCustomerId();
            if (!empty($aPaymentData) && $iCustomerId) {
                $this->savedPaymentData->addSavedPaymentData($iCustomerId, $this->getCode(), $aPaymentData);
            }
        }
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
     * Return capture parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificCaptureParameters(Order $oOrder)
    {
        return []; // filled in child classes
    }

    /**
     * Return debit parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificDebitParameters(Order $oOrder)
    {
        return []; // filled in child classes
    }

    /**
     * Return success url for redirect payment types
     *
     * @param  Order $oOrder
     * @return string
     */
    public function getSuccessUrl(?Order $oOrder = null)
    {
        $sAddedParams = '';
        if ($oOrder !== null) {
            $sAddedParams = '?incrementId='.$oOrder->getIncrementId();
        }
        return $this->url->getUrl('payone/onepage/returned').$sAddedParams;
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
     * Return if transaction_param has to be added to the authroization request
     *
     * @return bool
     */
    public function needsTransactionParam()
    {
        return $this->blNeedsTransactionParam;
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
     * Return if bank data has to be added to the debit request
     *
     * @return bool
     */
    public function needsSepaDataOnDebit()
    {
        return $this->blNeedsSepaDataOnDebit;
    }

    /**
     * Get config parameter for this payment type
     *
     * @param  string $sParam
     * @param  string $sStoreCode
     * @return string
     */
    public function getCustomConfigParam($sParam, $sStoreCode = null)
    {
        if ($sStoreCode === null) {
            $sStoreCode = $this->getStoreCode();
        }
        return $this->shopHelper->getConfigParam($sParam, $this->getCode(), 'payone_payment', $sStoreCode);
    }

    /**
     * Trys to retrieve the storecode from the order
     *
     * @return string|null
     */
    protected function getStoreCode()
    {
        try {
            $oInfoInstance = $this->getInfoInstance();
            if (empty($oInfoInstance)) {
                return null;
            }
        } catch (\Exception $oExc) {
            return null;
        }

        $oOrder = $oInfoInstance->getOrder();
        if (empty($oOrder)) {
            $oOrder = $oInfoInstance->getQuote();
            if (empty($oOrder)) {
                return null;
            }
        }

        $oStore = $oOrder->getStore();
        if (empty($oStore)) {
            return null;
        }
        return $oStore->getCode();
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

    /**
     * @return array
     */
    public function getFrontendConfig()
    {
        // Hook to be overloaded by child classes
        return [];
    }
}
