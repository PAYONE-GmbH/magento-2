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

use Magento\Sales\Model\Order;
use Payone\Core\Model\Methods\PayoneMethod;

/**
 * Class for the PAYONE Server API request "getfile"
 */
class Getfile extends Base
{
    /**
     * Send request "getfile" to PAYONE server API
     *
     * @param  Order        $oOrder
     * @param  PayoneMethod $oPayment
     * @return string
     */
    public function sendRequest(Order $oOrder, PayoneMethod $oPayment)
    {
        $sReturn = false;
        $sStatus = 'ERROR';
        $aResponse = [];

        $this->addParameter('request', 'getfile'); // Request method
        $this->addParameter('file_reference', $oOrder->getPayoneMandateId());
        $this->addParameter('file_type', 'SEPA_MANDATE');
        $this->addParameter('file_format', 'PDF');

        $this->addParameter('mode', $oOrder->getPayoneMode());
        if ($oOrder->getPayoneMode() == 'test') {
            $this->removeParameter('integrator_name');
            $this->removeParameter('integrator_version');
            $this->removeParameter('solution_name');
            $this->removeParameter('solution_version');
        }

        if ($oPayment->hasCustomConfig()) {// if payment type doesnt use the global settings
            $this->addCustomParameters($oPayment); // add custom connection settings
        }

        $aOptions = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($this->aParameters),
            ],
        ];
        $oContext = stream_context_create($aOptions);
        $sMandate = file_get_contents($this->sApiUrl, false, $oContext);
        if ($sMandate !== false) {
            $sReturn = $sMandate;
            $sStatus = 'SUCCESS';
            $aResponse['file'] = $oOrder->getPayoneMandateId().'.pdf';
        }

        $this->apiLog->addApiLogEntry($this->getParameters(), $aResponse, $sStatus, $this->getOrderId()); // log request to db
        return $sReturn;
    }
}
