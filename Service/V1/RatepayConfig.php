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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Service\V1;

use Payone\Core\Api\RatepayConfigInterface;

/**
 * Web API model for the PAYONE RatepayConfig
 */
class RatepayConfig implements RatepayConfigInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\RatepayConfigResponseFactory
     */
    protected $responseFactory;

    /**
     * Address repository
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE ratepay helper
     *
     * @var \Payone\Core\Helper\Ratepay
     */
    protected $ratepayHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Service\V1\Data\RatepayConfigResponseFactory $responseFactory
     * @param \Magento\Checkout\Model\Session                           $checkoutSession
     * @param \Payone\Core\Helper\Ratepay                               $ratepayHelper
     */
    public function __construct(
        \Payone\Core\Service\V1\Data\RatepayConfigResponseFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Helper\Ratepay $ratepayHelper
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->ratepayHelper = $ratepayHelper;
    }

    /**
     * PAYONE editAddress script
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @return \Payone\Core\Service\V1\Data\RatepayConfigResponse
     */
    public function getConfig($cartId)
    {
        $oResponse = $this->responseFactory->create();
        $oResponse->setData('config', json_encode($this->ratepayHelper->getRatepayConfig()));
        $oResponse->setData('success', true);
        return $oResponse;
    }
}
