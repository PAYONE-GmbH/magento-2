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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Api;

interface InstallmentPlanInterface
{
    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @param  string $birthday
     * @param  string $email
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getInstallmentPlan($cartId, $birthday, $email = false);

    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @param  string $calcType
     * @param  int $calcValue
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getInstallmentPlanRatepay($cartId, $calcType, $calcValue);

    /**
     * PAYONE BNPL installment plan getter
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @param  string $paymentCode
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getInstallmentPlanBNPL($cartId, $paymentCode);

    /**
     * Collects allowed runtimes afterwards
     * Needed for guest checkout since the billing country is not known when checkout is loaded
     *
     * @param  string $cartId
     * @return \Payone\Core\Service\V1\Data\InstallmentPlanResponse
     */
    public function getAllowedMonths($cartId);
}
