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

namespace Payone\Core\Service\V1;

use Payone\Core\Api\InstallmentPlanInterface;
use Payone\Core\Helper\Api;
use Payone\Core\Service\V1\Data\InstallmentPlanResponse;
use Payone\Core\Api\Data\InstallmentPlanResponseInterfaceFactory;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Api\Request\Genericpayment\Calculation;
use Payone\Core\Model\Api\Request\Genericpayment\PreCheck;
use Payone\Core\Model\Api\Request\Genericpayment\InstallmentOptions;
use Payone\Core\Model\Methods\Payolution\Installment;
use Payone\Core\Block\Payolution\InstallmentPlan as Block;
use Payone\Core\Block\BNPL\InstallmentPlan as BNPLBlock;
use Payone\Core\Helper\Ratepay;
use Payone\Core\Model\Methods\Ratepay\Installment as RatepayInstallment;

/**
 * Web API model for the PAYONE addresscheck
 */
class InstallmentPlan implements InstallmentPlanInterface
{
    /**
     * Factory for the response object
     *
     * @var InstallmentPlanResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session object
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Calculation Genericpayment request object
     *
     * @var Calculation
     */
    protected $calculation;

    /**
     * Payone Payolution Installment payment method
     *
     * @var Installment
     */
    protected $payolution;

    /**
     * InstallmentRate Block object
     *
     * @var Block
     */
    protected $block;

    /**
     * PreCheck Genericpayment request object
     *
     * @var PreCheck
     */
    protected $precheck;

    /**
     * @var Ratepay
     */
    protected $ratepayHelper;

    /**
     * @var RatepayInstallment
     */
    protected $ratepayInstallment;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * InstallmentOptions Genericpayment request object
     *
     * @var InstallmentOptions
     */
    protected $installmentOptions;

    /**
     * @var BNPLBlock
     */
    protected $bnplBlock;

    /**
     * Constructor.
     *
     * @param InstallmentPlanResponseInterfaceFactory $responseFactory
     * @param Session                                 $checkoutSession
     * @param PreCheck                                $precheck
     * @param Calculation                             $calculation
     * @param Installment                             $payolution
     * @param Block                                   $block
     * @param Ratepay                                 $ratepayHelper
     * @param RatepayInstallment                      $ratepayInstallment
     * @param Api                                     $apiHelper
     * @param InstallmentOptions                      $installmentOptions
     * @param BNPLBlock                               $bnplBlock
     */
    public function __construct(
        InstallmentPlanResponseInterfaceFactory $responseFactory,
        Session $checkoutSession,
        PreCheck $precheck,
        Calculation $calculation,
        Installment $payolution,
        Block $block,
        Ratepay $ratepayHelper,
        RatepayInstallment $ratepayInstallment,
        Api $apiHelper,
        InstallmentOptions $installmentOptions,
        BNPLBlock $bnplBlock
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->precheck = $precheck;
        $this->calculation = $calculation;
        $this->payolution = $payolution;
        $this->block = $block;
        $this->ratepayInstallment = $ratepayInstallment;
        $this->ratepayHelper = $ratepayHelper;
        $this->apiHelper = $apiHelper;
        $this->installmentOptions = $installmentOptions;
        $this->bnplBlock = $bnplBlock;
    }

    /**
     * Write installment draft download link array to session
     *
     * @param  $aInstallmentData
     * @return void
     */
    protected function setInstallmentDraftDownloadLinks($aInstallmentData)
    {
        $aDownloadLinks = array();
        foreach ($aInstallmentData as $aInstallment) {
            $aDownloadLinks[$aInstallment['duration']] = $aInstallment['standardcreditinformationurl'];
        }
        $this->checkoutSession->setInstallmentDraftLinks($aDownloadLinks);
    }

    /**
     * Check responses for errors and add them to the response object if needed
     *
     * @param  InstallmentPlanResponse $oResponse
     * @param  array                   $aResponsePreCheck
     * @param  array                   $aResponseCalculation
     * @return InstallmentPlanResponse
     */
    protected function checkForErrors($oResponse, $aResponsePreCheck, $aResponseCalculation)
    {
        $sErrorMessage = false;
        if (isset($aResponsePreCheck['status']) && $aResponsePreCheck['status'] == 'ERROR') {
            $sErrorMessage = __($aResponsePreCheck['errorcode'] . ' - ' . $aResponsePreCheck['customermessage']);
        } elseif (isset($aResponseCalculation['status']) && $aResponseCalculation['status'] == 'ERROR') {
            $sErrorMessage = __($aResponseCalculation['errorcode'] . ' - ' . $aResponseCalculation['customermessage']);
        } elseif (!$aResponsePreCheck || (isset($aResponsePreCheck['status']) && $aResponsePreCheck['status'] == 'OK' && !$aResponseCalculation)) {
            $sErrorMessage = __('An unknown error occurred');
        }
        if ($sErrorMessage !== false) {
            $oResponse->setData('errormessage', $sErrorMessage);
        }
        return $oResponse;
    }

    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @param  string $birthday
     * @param  string $email
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getInstallmentPlan($cartId, $birthday, $email = false)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false); // set success to false as default, set to true later if true

        $oQuote = $this->checkoutSession->getQuote();
        $aResponsePreCheck = $this->precheck->sendRequest($this->payolution, $oQuote, false, $birthday, $email);
        $aResponseCalculation = false;
        if (isset($aResponsePreCheck['status']) && $aResponsePreCheck['status'] == 'OK') {
            $aResponseCalculation = $this->calculation->sendRequest($this->payolution, $oQuote);
            $aInstallmentData = $this->parseResponse($aResponseCalculation);
            if (isset($aResponseCalculation['status']) && $aResponseCalculation['status'] == 'OK' && $aInstallmentData !== false) {
                $oResponse->setData('success', true); // set success to false as default, set to true later if true
                $this->setInstallmentDraftDownloadLinks($aInstallmentData);
                $this->checkoutSession->setInstallmentWorkorderId($aResponseCalculation['workorderid']);

                $this->block->setInstallmentData($aInstallmentData);
                $this->block->setCode($this->payolution->getCode());

                $oResponse->setData('installmentPlanHtml', $this->block->toHtml());
            }
        }
        $oResponse = $this->checkForErrors($oResponse, $aResponsePreCheck, $aResponseCalculation);
        return $oResponse;
    }

    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @param  string $calcType
     * @param  int $calcValue
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getInstallmentPlanRatepay($cartId, $calcType, $calcValue)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false); // set success to false as default, set to true later if true

        $oQuote = $this->checkoutSession->getQuote();

        $sRatepayShopId = $this->ratepayHelper->getRatepayShopId($this->ratepayInstallment->getCode(), $oQuote->getBillingAddress()->getCountryId(), $this->apiHelper->getCurrencyFromQuote($oQuote), $this->apiHelper->getQuoteAmount($oQuote));

        $aResponseCalculation = $this->calculation->sendRequestRatepay($this->ratepayInstallment, $oQuote, $sRatepayShopId, $calcType, $calcValue);
        if ($aResponseCalculation['status'] == "OK") {
            unset($aResponseCalculation['status']);
            unset($aResponseCalculation['workorderid']);
            $aInstallmentPlan = [];
            foreach ($aResponseCalculation as $sKey => $sValue) {
                $sKey = str_replace("add_paydata", "", $sKey);
                $sKey = str_replace(["[", "]"], "", $sKey);
                $sKey = str_replace("-", "_", $sKey);
                $aInstallmentPlan[$sKey] = $sValue;
            }
            $oResponse->setData('installmentPlan', json_encode($aInstallmentPlan));
            $oResponse->setData('success', true);
        }
        return $oResponse;
    }

    /**
     * Extract number from given string
     *
     * @param  string $sString
     * @return string|false
     */
    protected function getNumberFromString($sString)
    {
        preg_match('/^[^0-9]*_([0-9])$/m', $sString, $matches);

        if (count($matches) == 2) {
            return $matches[1];
        }
        return false;
    }

    protected function formatInstallmentOptions($aResponse)
    {
        unset($aResponse['status']);
        unset($aResponse['workorderid']);

        $aInstallmentOptions = ['runtimes' => []];

        foreach ($aResponse as $sKey => $sValue) {
            $sKey = str_replace("add_paydata", "", $sKey);
            $sKey = str_replace(["[", "]"], "", $sKey);
            $sKey = str_replace("-", "_", $sKey);

            $iIndex = $this->getNumberFromString($sKey);
            if ($iIndex !== false) {
                $sKey = str_replace("_".$iIndex, "", $sKey);
                if (!isset($aInstallmentOptions['runtimes'][$iIndex])) {
                    $aInstallmentOptions['runtimes'][$iIndex] = [];
                }
                $aInstallmentOptions['runtimes'][$iIndex][$sKey] = $sValue;
            } else {
                $aInstallmentOptions[$sKey] = $sValue;
            }
        }
        return $aInstallmentOptions;
    }

    /**
     * PAYONE BNPL installment plan getter
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @param  string $paymentCode
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getInstallmentPlanBNPL($cartId, $paymentCode)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false); // set success to false as default, set to true later if true

        $oQuote = $this->checkoutSession->getQuote();

        $aResponseCalculation = $this->installmentOptions->sendRequest($oQuote, $paymentCode);

        if ($aResponseCalculation['status'] == "OK") {
            $this->checkoutSession->setInstallmentWorkorderId($aResponseCalculation['workorderid']);
            $aInstallmentPlan = $this->formatInstallmentOptions($aResponseCalculation);

            $this->bnplBlock->setInstallmentData($aInstallmentPlan);
            $this->bnplBlock->setCode($paymentCode);

            $oResponse->setData('installmentPlanHtml', $this->bnplBlock->toHtml());
            $oResponse->setData('success', true);
        }
        return $oResponse;
    }

    /**
     * Collects allowed runtimes afterwards
     * Needed for guest checkout since the billing country is not known when checkout is loaded
     *
     * @param  string $cartId
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getAllowedMonths($cartId)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false); // set success to false as default, set to true later if true

        $oQuote = $this->checkoutSession->getQuote();

        $aAllowedMonths = $this->ratepayInstallment->getAllowedMonths($oQuote);

        $oResponse->setData('allowedMonths', json_encode($aAllowedMonths));
        $oResponse->setData('success', true);

        return $oResponse;
    }

    /**
     * @param array $aResponse
     * @return array
     */
    public function getPayDataArray($aResponse)
    {
        $aPayData = array();
        foreach($aResponse as $sKey => $sValue) {
            $sCorrectedKey = str_ireplace('add_paydata[', '', $sKey);
            $sCorrectedKey = rtrim($sCorrectedKey, ']');
            $sCorrectedKey = strtolower($sCorrectedKey);
            $sCorrectedKey = str_replace('-', '_', $sCorrectedKey);
            $aPayData[$sCorrectedKey] = $sValue;
        }

        ksort($aPayData);
        return $aPayData;
    }

    /**
     * Parse the response array into a readable array
     *
     * @param $aResponse
     * @return array|false
     */
    protected function parseResponse($aResponse)
    {
        $aInstallmentData = array();

        $aPayData = $this->getPayDataArray($aResponse);
        foreach ($aPayData as $sKey => $sValue) {
            $aSplit = explode('_', $sKey);
            for($i = count($aSplit); $i > 0; $i--) {
                if($i == count($aSplit)) {
                    $aTmp = array($aSplit[$i-1] => $sValue);
                } else {
                    $aTmp = array($aSplit[$i-1] => $aTmp);
                }
            }

            $aInstallmentData = array_replace_recursive($aInstallmentData, $aTmp);
        }

        if(isset($aInstallmentData['paymentdetails']) && count($aInstallmentData['paymentdetails']) > 0) {
            return $aInstallmentData['paymentdetails'];
        }

        return false;
    }
}
