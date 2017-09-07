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
 * Class for the PAYONE Server API request "updateuser"
 */
class Updateuser extends AddressRequest
{
    /**
     * Send request "updateuser" to PAYONE server API
     *
     * @param  Order        $oOrder
     * @param  PayoneMethod $oPayment
     * @param  string       $sPayOneUserId
     * @return array
     */
    public function sendRequest(Order $oOrder, PayoneMethod $oPayment, $sPayOneUserId)
    {
        $this->addParameter('request', 'updateuser');
        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('customerid', $sPayOneUserId);

        $this->setOrderId($oOrder->getRealOrderId());

        $oBilling = $oOrder->getBillingAddress();
        $this->addUserDataParameters(
            $oBilling,
            $oPayment,
            $oOrder->getCustomerGender(),
            $oOrder->getCustomerEmail(),
            $oOrder->getCustomerDob(),
            true
        );
        return $this->send($oPayment);
    }
}
