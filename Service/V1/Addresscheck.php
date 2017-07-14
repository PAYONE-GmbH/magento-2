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

namespace Payone\Core\Service\V1;

use Payone\Core\Api\AddresscheckInterface;
use Payone\Core\Service\V1\Data\AddresscheckResponse;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Web API model for the PAYONE addresscheck
 */
class Addresscheck implements AddresscheckInterface
{
    /**
     * PAYONE addresscheck request model
     *
     * @var \Payone\Core\Model\Risk\Addresscheck
     */
    protected $addresscheck;

    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\AddresscheckResponseFactory
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
     * @param \Payone\Core\Service\V1\Data\AddresscheckResponseFactory $responseFactory
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     */
    public function __construct(
        \Payone\Core\Model\Risk\Addresscheck $addresscheck,
        \Payone\Core\Service\V1\Data\AddresscheckResponseFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->addresscheck = $addresscheck;
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Generate the confirm message from the given address
     *
     * @param  AddressInterface $addressData
     * @return string
     */
    protected function getConfirmMessage(AddressInterface $addressData)
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
     * Add the score to the correct session variable
     *
     * @param  AddressInterface $oAddress
     * @param  bool             $blIsBillingAddress
     * @return void
     */
    protected function addScoreToSession(AddressInterface $oAddress, $blIsBillingAddress)
    {
        $sScore = $this->addresscheck->getScore($oAddress);
        if ($blIsBillingAddress === true) { // is billing address?
            $this->checkoutSession->setPayoneBillingAddresscheckScore($sScore);
        } else {
            $this->checkoutSession->setPayoneShippingAddresscheckScore($sScore);
        }
    }

    /**
     * Set error message if checkout is configured to stop on error or set success = true instead
     *
     * @param  AddresscheckResponse $oResponse
     * @return AddresscheckResponse
     */
    protected function handleErrorCase(AddresscheckResponse $oResponse)
    {
        $sHandleError = $this->addresscheck->getConfigParam('handle_response_error');
        if ($sHandleError == 'stop_checkout') {
            $oResponse->setData('errormessage', __($this->addresscheck->getErrorMessage())); // stop checkout with errormsg
        } elseif ($sHandleError == 'continue_checkout') {
            $oResponse->setData('success', true); // continue anyways
        }
        return $oResponse;
    }

    /**
     * Handle the response according to its return status
     *
     * @param  AddresscheckResponse $oResponse
     * @param  AddressInterface     $oAddress
     * @param  array                $aResponse
     * @return AddresscheckResponse
     */
    protected function handleResponse(AddresscheckResponse $oResponse, AddressInterface $oAddress, $aResponse)
    {
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
            $oResponse = $this->handleErrorCase($oResponse);
        }
        return $oResponse;
    }

    /**
     * Send addresscheck request and handle the response object
     *
     * @param  AddresscheckResponse $oResponse
     * @param  AddressInterface     $oAddress
     * @param  bool                 $blIsBillingAddress
     * @return AddresscheckResponse
     */
    protected function handleAddresscheck(AddresscheckResponse $oResponse, AddressInterface $oAddress, $blIsBillingAddress) {
        $aResponse = $this->addresscheck->getResponse($oAddress, $blIsBillingAddress);
        if (is_array($aResponse)) { // is a real response existing?
            $this->addScoreToSession($oAddress, $blIsBillingAddress);
            $oResponse = $this->handleResponse($oResponse, $oAddress, $aResponse);
        } elseif ($aResponse === true) { // check lifetime still valid, set success to true
            $oResponse->setData('success', true);
        }
        return $oResponse;
    }

    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @param  bool $isBillingAddress
     * @param  bool $isVirtual
     * @param  double $dTotal
     * @return \Payone\Core\Service\V1\Data\AddresscheckResponse
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
