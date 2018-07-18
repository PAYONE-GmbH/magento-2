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

namespace Payone\Core\Model\Api\Request;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Methods\PayoneMethod;

/**
 * Base class for all PAYONE API requests
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
abstract class Base
{
    /**
     * Order id
     *
     * @var string
     */
    protected $sOrderId = null;

    /**
     * Array or request parameters
     *
     * @var array
     */
    protected $aParameters = [];

    /**
     * Response of the request
     *
     * @var array
     */
    protected $aResponse = false;

    /**
     * URL of PAYONE Server API
     *
     * @var string
     */
    protected $sApiUrl = 'https://api.pay1.de/post-gateway/';

    /**
     * Map for custom parameters to be added $sParamName => $sConfigName
     *
     * @var array
     */
    protected $aCustomParamMap = [
        'mid' => 'mid',
        'portalid' => 'portalid',
        'aid' => 'aid',
        'key' => 'key',
        'request' => 'request',
    ];

    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * PAYONE environment helper
     *
     * @var \Payone\Core\Helper\Environment
     */
    protected $environmentHelper;

    /**
     * PAYONE api helper
     *
     * @var \Payone\Core\Helper\Api
     */
    protected $apiHelper;

    /**
     * API-log resource model
     *
     * @var \Payone\Core\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * Store id for the current context
     *
     * @var string
     */
    protected $storeCode = null;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog
    ) {
        $this->shopHelper = $shopHelper;
        $this->environmentHelper = $environmentHelper;
        $this->apiHelper = $apiHelper;
        $this->apiLog = $apiLog;
        $this->initRequest();
    }

    /**
     * Initialize request
     * Set all default parameters
     *
     * @return void
     */
    protected function initRequest()
    {
        $this->addParameter('mid', $this->shopHelper->getConfigParam('mid', 'global', 'payone_general', $this->storeCode)); // PayOne Merchant ID
        $this->addParameter('portalid', $this->shopHelper->getConfigParam('portalid', 'global', 'payone_general', $this->storeCode)); // PayOne Portal ID
        $this->addParameter('key', md5($this->shopHelper->getConfigParam('key', 'global', 'payone_general', $this->storeCode))); // PayOne Portal Key
        $this->addParameter('encoding', $this->environmentHelper->getEncoding()); // Encoding
        $this->addParameter('integrator_name', 'Magento2'); // Shop-system
        $this->addParameter('integrator_version', $this->shopHelper->getMagentoVersion()); // Shop version
        $this->addParameter('solution_name', 'fatchip'); // Company developing the module
        $this->addParameter('solution_version', PayoneConfig::MODULE_VERSION); // Module version
    }

    /**
     * Set current store code and reinit base parameters
     *
     * @param  string $sStoreCode
     * @return void
     */
    public function setStoreCode($sStoreCode)
    {
        if ($this->storeCode != $sStoreCode) {
            $this->storeCode = $sStoreCode;
            $this->initRequest(); //reinit base parameters
        }
    }

    /**
     * Add parameter to request
     *
     * @param  string $sKey               parameter key
     * @param  string $sValue             parameter value
     * @param  bool   $blAddAsNullIfEmpty add parameter with value NULL if empty. Default is false
     * @return void
     */
    public function addParameter($sKey, $sValue, $blAddAsNullIfEmpty = false)
    {
        if ($blAddAsNullIfEmpty === true && empty($sValue)) {
            $sValue = 'NULL'; // add value as string NULL - needed in certain situations
        }
        $this->aParameters[$sKey] = $sValue;
    }

    /**
     * Remove parameter from request
     *
     * @param  string $sKey parameter key
     * @return void
     */
    public function removeParameter($sKey)
    {
        if (array_key_exists($sKey, $this->aParameters)) {// is parameter set?
            unset($this->aParameters[$sKey]);
        }
    }

    /**
     * Get parameter from request or return false if parameter was not set
     *
     * @param  string $sKey parameter key
     * @return string|bool
     */
    public function getParameter($sKey)
    {
        if (array_key_exists($sKey, $this->aParameters)) {// is parameter set?
            return $this->aParameters[$sKey];
        }
        return false;
    }

    /**
     * Return all parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->aParameters;
    }

    /**
     * Set response array
     *
     * @param  $aResponse
     * @return void
     */
    public function setResponse($aResponse)
    {
        $this->aResponse = $aResponse;
    }

    /**
     * Return the response array
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->aResponse;
    }

    /**
     * Add non-global parameters specifically configured in the payment type
     *
     * @param  PayoneMethod $oPayment
     * @return void
     */
    protected function addCustomParameters(PayoneMethod $oPayment)
    {
        foreach ($this->aCustomParamMap as $sParamName => $sConfigName) {// add all custom parameters
            $sCustomConfig = $oPayment->getCustomConfigParam($sConfigName); // get custom config param
            if (!empty($sCustomConfig)) { // only add if the param is configured
                if ($sConfigName == 'key') {
                    $this->addParameter($sParamName, md5($sCustomConfig)); // key isn't hashed in db
                } else {
                    $this->addParameter($sParamName, $sCustomConfig); // add custom param to request
                }
            }
        }
    }

    /**
     * Set the order id that is associated with this request
     *
     * @param  string $sOrderId
     * @return void
     */
    public function setOrderId($sOrderId)
    {
        $this->sOrderId = $sOrderId;
    }

    /**
     * Return order id if set
     *
     * @return string
     */
    public function getOrderId()
    {
        if ($this->sOrderId !== null) {// was order id set?
            return $this->sOrderId;
        }
        return '';
    }

    /**
     * Add the redirect urls to the request
     *
     * @param  PayoneMethod $oPayment
     * @return void
     */
    protected function addRedirectUrls(PayoneMethod $oPayment)
    {
        $this->addParameter('successurl', $oPayment->getSuccessUrl());
        $this->addParameter('errorurl', $oPayment->getErrorUrl());
        $this->addParameter('backurl', $oPayment->getCancelUrl());
    }

    /**
     * Validate if all general required parameters are set
     *
     * @return bool
     */
    protected function validateParameters()
    {
        if ($this->getParameter('mid') === false || $this->getParameter('portalid') === false ||
            $this->getParameter('key') === false || $this->getParameter('mode') === false) {
            return false;
        }
        return true;
    }

    /**
     * Send the previously prepared request, log request and response into the database and return the response

     * @param  PayoneMethod $oPayment
     * @return array
     */
    protected function send(PayoneMethod $oPayment = null)
    {
        if ($oPayment !== null && $oPayment->hasCustomConfig()) { // if payment type doesnt use the global settings
            $this->addCustomParameters($oPayment); // add custom connection settings
        }

        if (!$this->validateParameters()) {// all base parameters existing?
            return ["errormessage" => "Payone API Setup Data not complete (API-URL, MID, AID, PortalID, Key, Mode)"];
        }
        
        $sRequestUrl = $this->apiHelper->getRequestUrl($this->getParameters(), $this->sApiUrl);
        $aResponse = $this->apiHelper->sendApiRequest($sRequestUrl); // send request to PAYONE
        $this->setResponse($aResponse);

        $this->apiLog->addApiLogEntry($this->getParameters(), $aResponse, $aResponse['status'], $this->getOrderId()); // log request to db

        return $aResponse;
    }
}
