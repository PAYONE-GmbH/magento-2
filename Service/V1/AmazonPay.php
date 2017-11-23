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

namespace Payone\Core\Service\V1;

use Payone\Core\Api\AmazonPayInterface;
use Payone\Core\Service\V1\Data\AmazonPayResponse;
use Payone\Core\Api\Data\AmazonPayResponseInterfaceFactory;

/**
 * Web API model for the PAYONE addresscheck
 */
class AmazonPay implements AmazonPayInterface
{
    /**
     * Factory for the response object
     *
     * @var AmazonPayResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Constructor.
     *
     * @param AmazonPayResponseInterfaceFactory $responseFactory
     */
    public function __construct(
        AmazonPayResponseInterfaceFactory $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @return \Payone\Core\Service\V1\Data\AmazonPayResponse
     */
    public function getWorkorderId() {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('workorderId', 'Blumenladen'); // set success to false as default, set to true later if true
        return $oResponse;
    }
}
