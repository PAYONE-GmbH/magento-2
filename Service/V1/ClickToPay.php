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
 * PHP version 8
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2026 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Service\V1;

use Payone\Core\Api\ClickToPayInterface;
use Payone\Core\Model\PayoneConfig;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

/**
 * Web API model for the PAYONE PayPal express
 */
class ClickToPay implements ClickToPayInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Api\Data\ClickToPayResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Request object
     *
     * @var \Payone\Core\Model\Api\Request\GetJWT
     */
    protected $jwtRequest;

    /**
     * @var \Payone\Core\Model\Methods\CreditcardV2
     */
    protected $ccv2PaymentMethod;

    /**
     * Constructor.
     *
     * @param \Payone\Core\Api\Data\ClickToPayResponseInterfaceFactory    $responseFactory
     * @param \Magento\Checkout\Model\Session                             $checkoutSession
     * @param \Payone\Core\Model\Api\Request\GetJWT                       $jwtRequest
     * @param \Payone\Core\Model\Methods\CreditcardV2                     $ccv2PaymentMethod
     */
    public function __construct(
        \Payone\Core\Api\Data\ClickToPayResponseInterfaceFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\GetJWT $jwtRequest,
        \Payone\Core\Model\Methods\CreditcardV2 $ccv2PaymentMethod,
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->jwtRequest = $jwtRequest;
        $this->ccv2PaymentMethod = $ccv2PaymentMethod;
    }

    /**
     * Get JWT for ClickToPay process
     *
     * @param  string $cartId
     * @return \Payone\Core\Service\V1\Data\ClickToPayResponse
     */
    public function getJwt($cartId)
    {
        $blSuccess = false;
        $oResponse = $this->responseFactory->create();

        $oQuote = $this->checkoutSession->getQuote();

        $oPayment = $oQuote->getPayment();
        $oPayment->setMethod(PayoneConfig::METHOD_CREDITCARDV2);

        $oQuote->setPayment($oPayment);
        $oQuote->save();

        $aResponse = $this->jwtRequest->sendRequest($this->ccv2PaymentMethod);
        if (isset($aResponse['status'], $aResponse['token']) && $aResponse['status'] == 'ok') {
            $blSuccess = true;

            $oResponse->setData('jwt', $aResponse['token']);
        }

        if (isset($aResponse['status'], $aResponse['customermessage']) && $aResponse['status'] == 'ERROR') {
            $oResponse->setData('errormessage', $aResponse['customermessage']);
        }

        $oResponse->setData('success', $blSuccess);
        return $oResponse;
    }
}
