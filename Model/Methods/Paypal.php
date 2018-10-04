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
 * Model for PayPal payment method
 */
class Paypal extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_PAYPAL;

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'wlt';

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

    /**
     * Return success url for redirect payment types
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        if ($this->checkoutSession->getIsPayonePayPalExpress() === true) {
            return $this->url->getUrl('payone/paypal/returned');
        }
        return parent::getSuccessUrl();
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aParams = ['wallettype' => 'PPE'];

        if ($this->checkoutSession->getIsPayonePayPalExpress() === true) {
            $sWorkorderId = $this->checkoutSession->getPayoneWorkorderId();
            if ($sWorkorderId) {
                $aParams['workorderid'] = $sWorkorderId;
            }
        }
        return $aParams;
    }
}
