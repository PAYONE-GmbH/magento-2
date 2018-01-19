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

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;

/**
 * Model for Barzahlen payment method
 */
class Barzahlen extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_BARZAHLEN;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'csh';

    /**
     * Returns authorization-mode
     * Barzahlen only supports preauthorization
     *
     * @return string
     */
    public function getAuthorizationMode()
    {
        return PayoneConfig::REQUEST_TYPE_PREAUTHORIZATION;
    }

    /**
     * Perform certain actions with the response
     *
     * @param  array $aResponse
     * @param  Order $oOrder
     * @return void
     */
    protected function handleResponse($aResponse, Order $oOrder)
    {
        if (isset($aResponse['status']) && $aResponse['status'] == 'APPROVED'
                && isset($aResponse['add_paydata[instruction_notes]'])) {
            $sInstructionNotes = urldecode($aResponse['add_paydata[instruction_notes]']);
            $this->checkoutSession->setPayoneInstructionNotes($sInstructionNotes);
        }
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        return [
            'cashtype' => 'BZN',
            'api_version' => '3.10',
        ];
    }
}
