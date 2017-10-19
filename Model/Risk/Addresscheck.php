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

namespace Payone\Core\Model\Risk;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;

/**
 * Model for calling the addresscheck request
 */
class Addresscheck
{
    /**
     * Response from the PAYONE addresscheck request
     *
     * @var array
     */
    protected $aResponse = null;

    /**
     * Determines if the request was NOT executed because the lifetime of the last was still valid
     *
     * @var bool
     */
    protected $blIsLifetimeValid;

    /**
     * Saves if the address was corrected
     *
     * @var bool
     */
    protected $addressCorrected;

    /**
     * PAYONE addresscheck request model
     *
     * @var \Payone\Core\Model\Api\Request\Addresscheck
     */
    protected $addresscheck;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\Api\Request\Addresscheck $addresscheck
     * @param \Payone\Core\Helper\Database                $databaseHelper
     * @param \Payone\Core\Helper\Toolkit                 $toolkitHelper
     * @param \Magento\Checkout\Model\Session             $checkoutSession
     */
    public function __construct(
        \Payone\Core\Model\Api\Request\Addresscheck $addresscheck,
        \Payone\Core\Helper\Database $databaseHelper,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->addresscheck = $addresscheck;
        $this->databaseHelper = $databaseHelper;
        $this->toolkitHelper = $toolkitHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Return if check was not executed because the lifetime of the last check was still valid
     *
     * @return bool
     */
    public function isLifetimeStillValid()
    {
        return $this->blIsLifetimeValid;
    }

    /**
     * Return addressCorrected property
     *
     * @return bool
     */
    public function isAddressCorrected()
    {
        return $this->addressCorrected;
    }

    /**
     * Get addresscheck config parameter
     *
     * @param  string $sParam
     * @return string
     */
    public function getConfigParam($sParam)
    {
        return $this->databaseHelper->getConfigParam($sParam, 'address_check', 'payone_protect');
    }

    /**
     * Correct a single address field if needed
     *
     * @param  AddressInterface $oAddress
     * @param  array            $aResponse
     * @param  string           $sArrayKey
     * @param  string           $sPropertyName
     * @return AddressInterface
     */
    protected function correctSingleField(AddressInterface $oAddress, $aResponse, $sArrayKey, $sPropertyName)
    {
        if (isset($aResponse[$sArrayKey]) && $aResponse[$sArrayKey] != $oAddress->getData($sPropertyName)) {
            $oAddress->setData($sPropertyName, $aResponse[$sArrayKey]);
            $this->addressCorrected = true;
        }

        return $oAddress;
    }

    /**
     * Change the address according to the response
     *
     * @param  AddressInterface $oAddress
     * @return AddressInterface
     */
    public function correctAddress(AddressInterface $oAddress)
    {
        $aResponse = $this->getResponse($oAddress);
        if (!is_array($aResponse)) {
            return $oAddress;
        }

        $this->addressCorrected = false;
        $oAddress = $this->correctSingleField($oAddress, $aResponse, 'firstname', 'firstname');
        $oAddress = $this->correctSingleField($oAddress, $aResponse, 'lastname', 'lastname');
        $oAddress = $this->correctSingleField($oAddress, $aResponse, 'zip', 'postcode');
        $oAddress = $this->correctSingleField($oAddress, $aResponse, 'city', 'city');

        if (isset($aResponse['street'])) {
            $sStreet = $oAddress->getStreet();
            if (is_array($sStreet)) {
                $sStreet = implode(' ', $sStreet);
            }

            if ($aResponse['street'] != $sStreet) {
                $oAddress->setStreet($aResponse['street']);
                $this->addressCorrected = true;
            }
        }

        return $oAddress;
    }

    /**
     * Check if the addresscheck needs to be executed for this quote
     *
     * @param  bool   $isBillingAddress
     * @param  bool   $isVirtual
     * @param  double $dTotal
     * @return bool
     */
    public function isCheckNeededForQuote($isBillingAddress, $isVirtual, $dTotal)
    {
        $dMinBasketValue = $this->getConfigParam('min_order_total');
        if (!empty($dMinBasketValue) && is_numeric($dMinBasketValue) && $dTotal < $dMinBasketValue) {
            return false;
        }

        $dMaxBasketValue = $this->getConfigParam('max_order_total');
        if (!empty($dMaxBasketValue) && is_numeric($dMaxBasketValue) && $dTotal > $dMaxBasketValue) {
            return false;
        }

        $blCheckVirtual = (bool)$this->getConfigParam('check_billing_for_virtual_order');
        if ($isBillingAddress === true && $isVirtual === true && $blCheckVirtual === false) {
            return false;
        }

        return true;
    }

    /**
     * Returns the personstatus mapping from addresscheck admin config
     *
     * @return array
     */
    public function getPersonstatusMapping()
    {
        $aReturnMappings = [];

        $sMappings = $this->getConfigParam('mapping_personstatus');
        $aMappings = $this->toolkitHelper->unserialize($sMappings);
        if (!is_array($aMappings)) {
            $aMappings = [];
        }

        foreach ($aMappings as $aMapping) {
            $aReturnMappings[$aMapping['personstatus']] = $aMapping['score'];
        }

        return $aReturnMappings;
    }

    /**
     * Get formatted invalid message
     *
     * @param  string $sCustomermessage
     * @return string
     */
    public function getInvalidMessage($sCustomermessage)
    {
        $sInvalidMessage = $this->getConfigParam('message_response_invalid');
        if (!empty($sInvalidMessage)) {
            $aSubstitutionArray = [
                '{{payone_customermessage}}' => __($sCustomermessage),
            ];
            return $this->toolkitHelper->handleSubstituteReplacement($sInvalidMessage, $aSubstitutionArray, 255);
        }

        return __($sCustomermessage);
    }

    /**
     * Return error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        $sErrorMessage = $this->getConfigParam('stop_checkout_message');
        if (empty($sErrorMessage)) { // add default errormessage if none is configured
            $sErrorMessage = 'An error occured during the addresscheck.';
        }
        return $sErrorMessage;
    }

    /**
     * Get error message by the given response
     *
     * @param  array $aResponse
     * @return string
     */
    public function getErrorMessageByResponse($aResponse)
    {
        $sErrorMessage = false;
        if (!isset($aResponse['status'])) { // the response doesnt have the expected format
            $sErrorMessage = 'An error occured during the addresscheck.';
        } elseif ($aResponse['status'] == 'INVALID') {
            $sErrorMessage = $this->getInvalidMessage($aResponse['customermessage']);
        } elseif ($aResponse['status'] == 'ERROR') {
            if ($this->getConfigParam('handle_response_error') == 'stop_checkout') {
                $sErrorMessage = $this->getErrorMessage();
            }
        }
        return $sErrorMessage;
    }

    /**
     * Execute addresscheck and return the response
     *
     * @param  AddressInterface $oAddress
     * @return array
     */

    /**
     *
     * @param AddressInterface $oAddress
     * @return type
     * @throws LocalizedException
     */
    protected function handleAddresscheck(AddressInterface $oAddress)
    {
        $aResponse = $this->getResponse($oAddress);
        if (is_array($aResponse)) {
            $sErrorMessage = $this->getErrorMessageByResponse($aResponse);
            if (!empty($sErrorMessage)) {
                throw new LocalizedException(__($sErrorMessage));
            }
        }
        return $aResponse;
    }

    /**
     * Get score from session or from a new addresscheck and add it to the address
     *
     * @param  AddressInterface $oAddress
     * @param  Quote            $oQuote
     * @param  bool             $blIsBillingAddress
     * @return AddressInterface
     */
    public function handleAddressManagement(AddressInterface $oAddress, Quote $oQuote, $blIsBillingAddress = true)
    {
        $sScore = $this->checkoutSession->getPayoneBillingAddresscheckScore();
        if ($blIsBillingAddress === false) {
            $sScore = $this->checkoutSession->getPayoneShippingAddresscheckScore();
        }
        $this->checkoutSession->unsPayoneBillingAddresscheckScore();
        $this->checkoutSession->unsPayoneShippingAddresscheckScore();

        if (!$sScore && empty($oAddress->getPayoneAddresscheckScore())) {
            if ($this->isCheckNeededForQuote(false, $oQuote->isVirtual(), $oQuote->getSubtotal())) {
                $aResponse = $this->handleAddresscheck($oAddress);
                if (isset($aResponse['status']) && $aResponse['status'] == 'VALID') {
                    $oAddress = $this->correctAddress($oAddress);
                }
                $sScore = $this->getScore($oAddress);
            }
        }
        if ($sScore) {
            $oAddress->setPayoneAddresscheckScore($sScore);
        }
        return $oAddress;
    }

    /**
     * Get score from response or an old saved score from the database
     *
     * @param  AddressInterface $oAddress
     * @return string
     */
    public function getScore(AddressInterface $oAddress)
    {
        $aResponse = $this->getResponse($oAddress);

        $sScore = 'G';
        if (isset($aResponse['status']) && $aResponse['status'] == 'INVALID') {
            $sScore = 'R';
        } elseif (isset($aResponse['personstatus'])) {
            $sPersonStatus = $aResponse['personstatus'];
            if ($sPersonStatus != 'NONE') {
                $aMapping = $this->getPersonstatusMapping();
                if (array_key_exists($sPersonStatus, $aMapping)) {
                    $sScore = $aMapping[$sPersonStatus];
                }
            }
        } elseif ($this->isLifetimeStillValid()) {
            $sScore = $this->databaseHelper->getOldAddressStatus($oAddress, false);
        }
        return $sScore;
    }

    /**
     * Perform the PAYONE addresscheck request and return the response
     *
     * @param  AddressInterface $oAddress
     * @param  bool             $blIsBillingAddress
     * @return array|bool
     */
    public function getResponse(AddressInterface $oAddress, $blIsBillingAddress = false)
    {
        if ($this->aResponse === null) {
            $this->aResponse = $this->addresscheck->sendRequest($oAddress, $blIsBillingAddress);
            if ($this->aResponse === true) {
                $this->blIsLifetimeValid = true;
            }
        }
        return $this->aResponse;
    }
}
