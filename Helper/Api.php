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

namespace Payone\Core\Helper;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Sales\Model\Order as SalesOrder;

/**
 * Helper class for everything that has to do with APIs
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
class Api extends \Payone\Core\Helper\Base
{
    /**
     * PAYONE connection curl php
     *
     * @var \Payone\Core\Helper\Connection\CurlPhp
     */
    protected $connCurlPhp;

    /**
     * PAYONE connection curl cli
     *
     * @var \Payone\Core\Helper\Connection\CurlCli
     */
    protected $connCurlCli;

    /**
     * PAYONE connection fsockopen
     *
     * @var \Payone\Core\Helper\Connection\Fsockopen
     */
    protected $connFsockopen;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Connection\CurlPhp     $connCurlPhp
     * @param \Payone\Core\Helper\Connection\CurlCli     $connCurlCli
     * @param \Payone\Core\Helper\Connection\Fsockopen   $connFsockopen
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Connection\CurlPhp $connCurlPhp,
        \Payone\Core\Helper\Connection\CurlCli $connCurlCli,
        \Payone\Core\Helper\Connection\Fsockopen $connFsockopen
    ) {
        parent::__construct($context, $storeManager);
        $this->connCurlPhp = $connCurlPhp;
        $this->connCurlCli = $connCurlCli;
        $this->connFsockopen = $connFsockopen;
    }

    /**
     * Check which communication possibilities are existing and send the request
     *
     * @param  string $sRequestUrl
     * @return array
     */
    public function sendApiRequest($sRequestUrl)
    {
        $aParsedRequestUrl = parse_url($sRequestUrl);
        if ($aParsedRequestUrl === false) {
            return ["errormessage" => "Payone API request URL could not be parsed."];
        }

        if ($this->connCurlPhp->isApplicable()) {
            // php native curl exists so we gonna use it for requesting
            $aResponse = $this->connCurlPhp->sendCurlPhpRequest($aParsedRequestUrl);
        } elseif ($this->connCurlCli->isApplicable()) {
            // cli version of curl exists on server
            $aResponse = $this->connCurlCli->sendCurlCliRequest($aParsedRequestUrl);
        } else {
            // last resort => via sockets
            $aResponse = $this->connFsockopen->sendSocketRequest($aParsedRequestUrl);
        }

        $aResponse = $this->formatOutputByResponse($aResponse);

        return $aResponse;
    }

    /**
     * Format response to a clean output array
     *
     * @param  array $aResponse
     * @return array
     */
    protected function formatOutputByResponse($aResponse)
    {
        $aOutput = [];

        if (is_array($aResponse)) { // correct response existing?
            foreach ($aResponse as $iLinenum => $sLine) { // go through line by line
                $iPos = strpos($sLine, "=");
                if ($iPos > 0) { // is a "=" as delimiter existing?
                    $aOutput[substr($sLine, 0, $iPos)] = trim(substr($sLine, $iPos + 1));
                } elseif (!empty($sLine)) { // is line not empty?
                    $aOutput[$iLinenum] = $sLine; // add the line unedited
                }
            }
        }

        return $aOutput;
    }

    /**
     * Generate the request url out of the params and die api url
     *
     * @param  array  $aParameters
     * @param  string $sApiUrl
     * @return string
     */
    public function getRequestUrl($aParameters, $sApiUrl)
    {
        $sRequestUrl = '';
        foreach ($aParameters as $sKey => $mValue) {
            if (is_array($mValue)) { // might be array
                foreach ($mValue as $i => $sSubValue) {
                    $sRequestUrl .= "&".$sKey."[".$i."]=".urlencode($sSubValue);
                }
            } else {
                $sRequestUrl .= "&".$sKey."=".urlencode($mValue);
            }
        }
        $sRequestUrl = $sApiUrl."?".substr($sRequestUrl, 1);
        return $sRequestUrl;
    }

    /**
     * Add PAYONE information to the order object to be saved in the DB
     *
     * @param  SalesOrder $oOrder
     * @param  array      $aRequest
     * @param  array      $aResponse
     * @return void
     */
    public function addPayoneOrderData(SalesOrder $oOrder, $aRequest, $aResponse)
    {
        if (isset($aResponse['txid'])) {// txid existing?
            $oOrder->setPayoneTxid($aResponse['txid']); // add txid to order entity
        }
        $oOrder->setPayoneRefnr($aRequest['reference']); // add refnr to order entity
        $oOrder->setPayoneAuthmode($aRequest['request']); // add authmode to order entity
        $oOrder->setPayoneMode($aRequest['mode']); // add payone mode to order entity
        if (isset($aRequest['mandate_identification'])) {// mandate id existing in request?
            $oOrder->setPayoneMandateId($aRequest['mandate_identification']);
        } elseif (isset($aResponse['mandate_identification'])) {// mandate id existing in response?
            $oOrder->setPayoneMandateId($aResponse['mandate_identification']);
        }
        if (isset($aResponse['clearing_reference'])) {
            $oOrder->setPayoneClearingReference($aResponse['clearing_reference']);
        }
        if (isset($aResponse['add_paydata[clearing_reference]'])) {
            $oOrder->setPayoneClearingReference($aResponse['add_paydata[clearing_reference]']);
        }
        if (isset($aResponse['add_paydata[workorderid]'])) {
            $oOrder->setPayoneWorkorderId($aResponse['add_paydata[workorderid]']);
        }
    }

    /**
     * Check if invoice-data has to be added to the authorization request
     *
     * @param  PayoneMethod $oPayment
     * @return bool
     */
    public function isInvoiceDataNeeded(PayoneMethod $oPayment)
    {
        $blInvoiceEnabled = (bool)$this->getConfigParam('transmit_enabled', 'invoicing'); // invoicing enabled?
        if ($blInvoiceEnabled || $oPayment->needsProductInfo()) {
            return true; // invoice data needed
        }
        return false; // invoice data not needed
    }
}
