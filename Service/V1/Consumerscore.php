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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Service\V1;

use Payone\Core\Api\ConsumerscoreInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Payone\Core\Model\Source\CreditratingIntegrationEvent as Event;
use Payone\Core\Service\V1\Data\ConsumerscoreResponse;

/**
 * Web API model for the PAYONE consumerscore
 */
class Consumerscore implements ConsumerscoreInterface
{
    /**
     * PAYONE addresscheck request model
     *
     * @var \Payone\Core\Model\Risk\Addresscheck
     */
    protected $addresscheck;

    /**
     * PAYONE consumerscore request model
     *
     * @var \Payone\Core\Model\Api\Request\Consumerscore
     */
    protected $consumerscore;

    /**
     * Consumerscore helper
     *
     * @var \Payone\Core\Helper\Consumerscore
     */
    protected $consumerscoreHelper;

    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\ConsumerscoreResponseFactory
     */
    protected $responseFactory;

    /**
     * Checkout session object
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Object of CheckedAddresses resource
     *
     * @var \Payone\Core\Model\ResourceModel\CheckedAddresses
     */
    protected $addressesChecked;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\Risk\Addresscheck                      $addresscheck
     * @param \Payone\Core\Service\V1\Data\ConsumerscoreResponseFactory $responseFactory
     * @param \Magento\Checkout\Model\Session                           $checkoutSession
     * @param \Payone\Core\Helper\Consumerscore                         $consumerscoreHelper
     * @param \Payone\Core\Model\Api\Request\Consumerscore              $consumerscore
     * @param \Payone\Core\Model\ResourceModel\CheckedAddresses         $addressesChecked
     */
    public function __construct(
        \Payone\Core\Model\Risk\Addresscheck $addresscheck,
        \Payone\Core\Service\V1\Data\ConsumerscoreResponseFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Helper\Consumerscore $consumerscoreHelper,
        \Payone\Core\Model\Api\Request\Consumerscore $consumerscore,
        \Payone\Core\Model\ResourceModel\CheckedAddresses $addressesChecked
    ) {
        $this->addresscheck = $addresscheck;
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->consumerscoreHelper = $consumerscoreHelper;
        $this->consumerscore = $consumerscore;
        $this->addressesChecked = $addressesChecked;
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
     * @param  ConsumerscoreResponse $oResponse
     * @return ConsumerscoreResponse
     */
    protected function handleErrorCase(ConsumerscoreResponse $oResponse)
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
     * @param  ConsumerscoreResponse $oResponse
     * @param  AddressInterface      $oAddress
     * @param  array                 $aResponse
     * @return ConsumerscoreResponse
     */
    protected function handleResponse(ConsumerscoreResponse $oResponse, AddressInterface $oAddress, $aResponse)
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
     * Return address hash
     *
     * @param bool $isBillingAddress
     * @return string|null
     */
    protected function getAddressHashFromSession($isBillingAddress)
    {
        if ($isBillingAddress === true) {
            return $this->checkoutSession->getPayoneBillingConsumerscoreHash();
        }
        return $this->checkoutSession->getPayoneShippingConsumerscoreHash();
    }

    /**
     * Add address hash to session
     *
     * @param string $sAddressHash
     * @param bool   $isBillingAddress
     * @return void
     */
    protected function setAddressHashInSession($sAddressHash, $isBillingAddress)
    {
        if ($isBillingAddress === true) {
            $this->checkoutSession->setPayoneBillingConsumerscoreHash($sAddressHash);
        } else {
            $this->checkoutSession->setPayoneShippingConsumerscoreHash($sAddressHash);
        }
    }

    /**
     * Checks for response in the session otherwise executes new Consumerscore
     *
     * @param AddressInterface $oAddress
     * @param bool             $isBillingAddress
     * @return array|bool
     */
    protected function getResponse(AddressInterface $oAddress, $isBillingAddress)
    {
        $aSessionResponse = $this->checkoutSession->getPayoneConsumerscoreResponse();
        $sAddressHash = $this->addressesChecked->getHashFromAddress($oAddress).($isBillingAddress ? 'b' : 's');
        if (!empty($aSessionResponse) && $this->getAddressHashFromSession($isBillingAddress) == $sAddressHash) {
            return $aSessionResponse;
        }
        $aResponse = $this->consumerscore->sendRequest($oAddress);
        $this->checkoutSession->setPayoneConsumerscoreResponse($aResponse);
        $this->setAddressHashInSession($sAddressHash, $isBillingAddress);
        return $aResponse;
    }

    /**
     * Send addresscheck request and handle the response object
     *
     * @param  ConsumerscoreResponse $oResponse
     * @param  AddressInterface      $oAddress
     * @param  bool                  $isBillingAddress
     * @return ConsumerscoreResponse
     */
    protected function handleBonicheck(ConsumerscoreResponse $oResponse, AddressInterface $oAddress, $isBillingAddress)
    {
        $aResponse = $this->getResponse($oAddress, $isBillingAddress);
        if (is_array($aResponse)) { // is a real response existing?
            $this->addresscheck->setResponse($aResponse);
            $this->addScoreToSession($oAddress, $isBillingAddress);
            $oResponse = $this->handleResponse($oResponse, $oAddress, $aResponse);
        } elseif ($aResponse === true) { // check lifetime still valid, set success to true
            $oResponse->setData('success', true);
        }
        return $oResponse;
    }

    /**
     * PAYONE consumerscore
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @param  bool                                     $isBillingAddress
     * @param  bool                                     $isVirtual
     * @param  double                                   $dTotal
     * @param  string                                   $sIntegrationEvent
     * @return \Payone\Core\Service\V1\Data\ConsumerscoreResponse
     */
    public function executeConsumerscore(\Magento\Quote\Api\Data\AddressInterface $addressData, $isBillingAddress, $isVirtual, $dTotal, $sIntegrationEvent)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', false); // set success to false as default, set to true later if true
        if ($this->consumerscoreHelper->isCreditratingNeeded($sIntegrationEvent, $dTotal) === true) {
            $oResponse = $this->handleBonicheck($oResponse, $addressData, $isBillingAddress);
        }
        return $oResponse;
    }
}
