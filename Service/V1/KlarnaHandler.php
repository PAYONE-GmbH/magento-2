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

use Payone\Core\Api\KlarnaHandlerInterface;
use Payone\Core\Service\V1\Data\AddresscheckResponse;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Web API model for the PAYONE addresscheck
 */
class KlarnaHandler implements KlarnaHandlerInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\KlarnaHandlerResponseFactory
     */
    protected $responseFactory;

    /**
     * Payone StartSession request object
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\StartSession
     */
    protected $startSession;

    /**
     * Payment helper object
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Payone\Core\Service\V1\Data\KlarnaHandlerResponseFactory     $responseFactory
     * @param \Payone\Core\Model\Api\Request\Genericpayment\StartSession    $startSession
     * @param \Magento\Payment\Helper\Data                                  $dataHelper
     * @param \Magento\Checkout\Model\Session                               $checkoutSession
     */
    public function __construct(
        \Payone\Core\Service\V1\Data\KlarnaHandlerResponseFactory $responseFactory,
        \Payone\Core\Model\Api\Request\Genericpayment\StartSession $startSession,
        \Magento\Payment\Helper\Data $dataHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->responseFactory = $responseFactory;
        $this->startSession = $startSession;
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * PAYONE Klarna handler
     *
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  string $paymentCode
     * @param  double $shippingCosts
     * @param  string $customerEmail
     * @return \Payone\Core\Service\V1\Data\KlarnaHandlerResponse
     */
    public function startKlarnaSession($paymentCode, $shippingCosts, $customerEmail)
    {
        $oResponse = $this->responseFactory->create();
        $blSuccess = false;

        $oMethodInstance = $this->dataHelper->getMethodInstance($paymentCode);
        if (!empty($oMethodInstance)) {
            $oQuote = $this->checkoutSession->getQuote();

            $aResponse = $this->startSession->sendRequest($oQuote, $oMethodInstance, $shippingCosts, $customerEmail);

            if (isset($aResponse['status'])) {
                if ($aResponse['status'] == 'OK') {
                    $oResponse->setData('clientToken', $aResponse['add_paydata[client_token]']);
                    $blSuccess = true;
                } elseif($aResponse['status'] == 'ERROR') {
                    if ($aResponse['errorcode'] == '981') {
                        $oResponse->setData('errormessage', __('Payment method is not available anymore'));
                    } else {
                        $oResponse->setData('errormessage', $aResponse['customermessage']);
                    }
                }
            }
            $oResponse->setData('success', $blSuccess);
        }

        return $oResponse;
    }
}
