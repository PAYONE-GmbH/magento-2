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
 * @copyright 2003 - 2025 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

declare(strict_types=1);

namespace Payone\Core\Model\Plugins;

use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\Quote as QuoteEntity;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Model\PayoneConfig;

class CustomerManagementPlugin
{
    protected $aPaymentMethodWhitelist = [
        PayoneConfig::METHOD_PAYPAL,
        PayoneConfig::METHOD_PAYPALV2,
        PayoneConfig::METHOD_AMAZONPAYV2,
    ];

    /**
     * Around plugin for the validateAddresses method
     * Skip address validation for guest customers when using Payone Express payment methods
     *
     * @param CustomerManagement $subject
     * @param \Closure $proceed
     * @param QuoteEntity $quote
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundValidateAddresses(CustomerManagement $subject, \Closure $proceed, QuoteEntity $quote): void
    {
        if ($quote->getCustomerIsGuest() && in_array($quote->getPayment()->getMethod(), $this->aPaymentMethodWhitelist)) {
            $methodInstance = $quote->getPayment()->getMethodInstance();
            if ($methodInstance instanceof PayoneMethod && $methodInstance->isExpressPayment() === true) {
                return;
            }
        }
        $proceed($quote);
    }
}
