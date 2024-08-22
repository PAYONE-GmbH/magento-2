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

interface AmazonPayInterface
{
    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $amazonReferenceId
     * @param  string $amazonAddressToken
     * @return \Payone\Core\Service\V1\Data\AmazonPayResponse
     */
    public function getWorkorderId($amazonReferenceId, $amazonAddressToken);

    /**
     * Returns Amazon Pay V2 checkout session payload
     *
     * @param  string $cartId
     * @return \Payone\Core\Service\V1\Data\AmazonPayResponse
     */
    public function getCheckoutSessionPayload($cartId);

    /**
     * Returns Amazon Pay V2 checkout session payload for APB
     *
     * @param  string $orderId
     * @return \Payone\Core\Service\V1\Data\AmazonPayResponse
     */
    public function getAmazonPayApbSession($orderId);
}
