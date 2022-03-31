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

namespace Payone\Core\Service\V1;

use Payone\Core\Api\ApplePayInterface;
use Payone\Core\Service\V1\Data\AddresscheckResponse;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Web API model for the PAYONE addresscheck
 */
class ApplePay implements ApplePayInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\KlarnaHandlerResponseFactory
     */
    protected $responseFactory;

    /**
     * Payone ApplePay session handler
     *
     * @var \Payone\Core\Model\ApplePay\SessionHandler
     */
    protected $sessionHandler;

    /**
     * Constructor
     *
     * @param \Payone\Core\Service\V1\Data\ApplePayResponseFactory $responseFactory
     * @param \Payone\Core\Model\ApplePay\SessionHandler           $sessionHandler
     */
    public function __construct(
        \Payone\Core\Service\V1\Data\ApplePayResponseFactory $responseFactory,
        \Payone\Core\Model\ApplePay\SessionHandler $sessionHandler
    ) {
        $this->responseFactory = $responseFactory;
        $this->sessionHandler = $sessionHandler;
    }

    /**
     * PAYONE Klarna handler
     *
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $cartId
     * @return \Payone\Core\Service\V1\Data\ApplePayResponse
     */
    public function getApplePaySession($cartId)
    {
        $oResponse = $this->responseFactory->create();
        $blSuccess = false;

        try {
            $sSession = $this->sessionHandler->getApplePaySession();
            if (!empty($sSession)) {
                $oResponse->setData('session', $sSession);
                $blSuccess = true;
            }
        } catch (\Exception $exc) {
            $oResponse->setData('errormessage', $exc->getMessage());
        }

        $oResponse->setData('success', $blSuccess);

        return $oResponse;
    }
}
