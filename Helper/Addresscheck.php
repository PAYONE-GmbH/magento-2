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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Helper;

/**
 * Helper class for everything that has to do with the addresscheck request
 */
class Addresscheck extends \Payone\Core\Helper\Base
{
    /**
     * Check enabled status
     *
     * @param  bool $blIsBillingAddress
     * @return bool
     */
    public function isCheckEnabled($blIsBillingAddress)
    {
        if (!$this->shopHelper->getConfigParam('enabled', 'address_check', 'payone_protect')) {
            return false; // address check was disabled
        }
        if ($blIsBillingAddress && $this->shopHelper->getConfigParam('check_billing', 'address_check', 'payone_protect') == 'NO') {
            return false; // address check was disabled for the billing address
        }
        if (!$blIsBillingAddress && $this->shopHelper->getConfigParam('check_shipping', 'address_check', 'payone_protect') == 'NO') {
            return false; // address check was disabled for the shipping address
        }
        return true;
    }
}
