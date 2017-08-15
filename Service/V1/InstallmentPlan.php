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
use Payone\Core\Service\V1\Data\InstallmentPlanResponse;
use Payone\Core\Api\Data\InstallmentPlanResponseInterfaceFactory;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Api\Request\Genericpayment\Calculation;
use Payone\Core\Model\Api\Request\Genericpayment\PreCheck;
use Payone\Core\Model\Methods\Payolution\Installment;
use Payone\Core\Block\Payolution\InstallmentPlan as Block;

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
    protected $payment;

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
     * Constructor.
     *
     * @param InstallmentPlanResponseInterfaceFactory $responseFactory
     * @param Session                                 $checkoutSession
     * @param PreCheck                                $precheck
     * @param Calculation                             $calculation
     * @param Installment                             $payment
     * @param Block                                   $block
     */
    public function __construct(
        InstallmentPlanResponseInterfaceFactory $responseFactory,
        Session $checkoutSession,
        PreCheck $precheck,
        Calculation $calculation,
        Installment $payment,
        Block $block
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->precheck = $precheck;
        $this->calculation = $calculation;
        $this->payment = $payment;
        $this->block = $block;
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
     * @param  string $birthday
     * @param  string $email
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getInstallmentPlan($birthday, $email = false)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false); // set success to false as default, set to true later if true

        $oQuote = $this->checkoutSession->getQuote();

        $aResponsePreCheck = $this->precheck->sendRequest($this->payment, $oQuote, $oQuote->getBaseGrandTotal(), $birthday, $email);
        $aResponseCalculation = false;
        if (isset($aResponsePreCheck['status']) && $aResponsePreCheck['status'] == 'OK') {
            $aResponseCalculation = $this->calculation->sendRequest($this->payment, $oQuote, $oQuote->getBaseGrandTotal());
            $aInstallmentData = $this->parseResponse($aResponseCalculation);
            if (isset($aResponseCalculation['status']) && $aResponseCalculation['status'] == 'OK' && $aInstallmentData !== false) {
                $oResponse->setData('success', true); // set success to false as default, set to true later if true
                $this->setInstallmentDraftDownloadLinks($aInstallmentData);
                $this->checkoutSession->setInstallmentWorkorderId($aResponseCalculation['workorderid']);

                $this->block->setInstallmentData($aInstallmentData);
                $this->block->setCode($this->payment->getCode());

                $oResponse->setData('installmentPlanHtml', $this->block->toHtml());
            }
        }
        $oResponse = $this->checkForErrors($oResponse, $aResponsePreCheck, $aResponseCalculation);
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
