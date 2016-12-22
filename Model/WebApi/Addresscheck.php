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

namespace Payone\Core\Model\WebApi;

use Payone\Core\Model\WebApi\AddresscheckResponse;

/**
 * Web API model for the PAYONE addresscheck
 */
class Addresscheck
{
    /**
     * PAYONE addresscheck request model
     *
     * @var \Payone\Core\Model\Risk\Addresscheck
     */
    protected $addresscheck;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Model\WebApi\AddresscheckResponseFactory
     */
    protected $responseFactory;

    /**
     * Checkout session object
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\Risk\Addresscheck                  $addresscheck
     * @param \Payone\Core\Helper\Toolkit                           $toolkitHelper
     * @param \Payone\Core\Model\WebApi\AddresscheckResponseFactory $responseFactory
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     */
    public function __construct(
        \Payone\Core\Model\Risk\Addresscheck $addresscheck,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Model\WebApi\AddresscheckResponseFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->addresscheck = $addresscheck;
        $this->toolkitHelper = $toolkitHelper;
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Generate the confirm message from the given address
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @return string
     */
    protected function getConfirmMessage(\Magento\Quote\Api\Data\AddressInterface $addressData)
    {
        $sMessage  = __('Address corrected. Please confirm.')."\n\n";
        $sMessage .= $addressData->getFirstname().' '.$addressData->getLastname()."\n";

        $mStreet = $addressData->getStreet();
        if (is_array($mStreet)) { // address can be string
            $sMessage .= $mStreet[0]."\n"; // add first line of address array
        } else {
            $sMessage .= $mStreet."\n"; // add string directly
        }
        $sMessage .= $addressData->getPostcode().' '.$addressData->getCity();

        return $sMessage;
    }

    /**
     * Return error message
     *
     * @return string
     */
    protected function getErrorMessage()
    {
        $sErrorMessage = $this->toolkitHelper->getConfigParam('stop_checkout_message', 'address_check', 'payone_protect');
        if (empty($sErrorMessage)) { // add default errormessage if none is configured
            $sErrorMessage = 'An error occured during the addresscheck.';
        }
        return $sErrorMessage;
    }

    /**
     * Add the score to the correct session variable
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $oAddress
     * @param  bool                                     $blIsBillingAddress
     * @return void
     */
    protected function addScoreToSession(\Magento\Quote\Api\Data\AddressInterface $oAddress, $blIsBillingAddress)
    {
        $sScore = $this->addresscheck->getScore($oAddress);
        if ($blIsBillingAddress === true) { // is billing address?
            $this->checkoutSession->setPayoneBillingAddresscheckScore($sScore);
        } else {
            $this->checkoutSession->setPayoneShippingAddresscheckScore($sScore);
        }
    }

    /**
     * Send addresscheck request and handle the response object
     *
     * @param  AddresscheckResponse                     $oResponse
     * @param  \Magento\Quote\Api\Data\AddressInterface $oAddress
     * @param  bool                                     $blIsBillingAddress
     * @return AddresscheckResponse
     */
    protected function handleAddresscheck(
        AddresscheckResponse $oResponse,
        \Magento\Quote\Api\Data\AddressInterface $oAddress,
        $blIsBillingAddress
    ) {
        $aResponse = $this->addresscheck->getResponse($oAddress, $blIsBillingAddress);
        if (is_array($aResponse)) { // is a real response existing?
            $this->addScoreToSession($oAddress, $blIsBillingAddress);

            if ($aResponse['status'] == 'VALID') { // data was checked successfully
                $oAddress = $this->addresscheck->correctAddress($oAddress);
                if ($this->addresscheck->isAddressCorrected() === true) { // was address changed?
                    $oResponse->setData('correctedAddress', $oAddress);
                    $oResponse->setData('confirmMessage', $this->getConfirmMessage($oAddress));
                }
                $oResponse->setData('success', true);
            } elseif ($aResponse['status'] == 'INVALID') { // given data invalid
                $oResponse->setData('errormessage', $this->addresscheck->getInvalidMessage($aResponse['customermessage']));
            } elseif ($aResponse['status'] == 'ERROR') { // an error occured in the API
                $sHandleError = $this->toolkitHelper->getConfigParam('handle_response_error', 'address_check', 'payone_protect');
                if ($sHandleError == 'stop_checkout') {
                    $oResponse->setData('errormessage', __($this->getErrorMessage())); // stop checkout with errormsg
                } elseif ($sHandleError == 'continue_checkout') {
                    $oResponse->setData('success', true); // continue anyways
                }
            }
        } elseif ($aResponse === true) { // check lifetime still valid, set success to true
            $oResponse->setData('success', true);
        }
        return $oResponse;
    }

    /**
     * PAYONE addresscheck
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @param  bool $isBillingAddress
     * @param  bool $isVirtual
     * @param  double $dTotal
     * @return \Payone\Core\Model\WebApi\AddresscheckResponse
     */
    public function checkAddress(\Magento\Quote\Api\Data\AddressInterface $addressData, $isBillingAddress, $isVirtual, $dTotal)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false); // set success to false as default, set to true later if true
        if ($this->addresscheck->isCheckNeededForQuote($isBillingAddress, $isVirtual, $dTotal)) {
            $oResponse = $this->handleAddresscheck($oResponse, $addressData, $isBillingAddress);
        }
        return $oResponse;
    }
}
