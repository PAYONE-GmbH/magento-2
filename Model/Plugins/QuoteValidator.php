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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Plugins;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteValidator as OrigQuoteValidator;
use Payone\Core\Model\PayoneConfig;

class QuoteValidator
{
    /**
     * This softens the customer validation for AmazonPay
     *
     * Since AmazonPay doesnt provide all information that can be configured as required in Magento2 this is needed for
     * some shop configurations because otherwise Magento2 will throw an exception and the AmazonPay cant be created
     *
     * @param  OrigQuoteValidator $subject
     * @param  callable           $proceed
     * @param  Quote              $quote
     * @return OrigQuoteValidator
     */
    public function aroundValidateBeforeSubmit(OrigQuoteValidator $subject, callable $proceed, Quote $quote) {
        if ($quote->getPayment()->getMethod() == PayoneConfig::METHOD_AMAZONPAY) {
            return $subject;
        }
        return $proceed($quote);
    }
}
