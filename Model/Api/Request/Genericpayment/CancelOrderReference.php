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

namespace Payone\Core\Model\Api\Request\Genericpayment;

use Magento\Quote\Model\Quote;
use Payone\Core\Model\Methods\PayoneMethod;

/**
 * Class for the PAYONE Server API request genericpayment - "cancelorderreference"
 */
class CancelOrderReference extends Base
{
    /**
     * Logger object
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger = null;

    /**
     * URL helper
     *
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Psr\Log\LoggerInterface                $logger
     * @param \Magento\Framework\Url                  $url
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Url $url
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog, $customerHelper);
        $this->logger = $logger;
        $this->url = $url;
    }

    /**
     * Reserves order id if not set yet
     * Returns reserved order id
     *
     * @param  Quote        $oQuote
     * @param  PayoneMethod $oPayment
     * @return string
     */
    protected function getReservedOrderId(Quote $oQuote, PayoneMethod $oPayment)
    {
        if (!$oQuote->getReservedOrderId()) {
            try {
                $oQuote->reserveOrderId()->save();
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $sRefNr = $this->shopHelper->getConfigParam('ref_prefix').$oQuote->getReservedOrderId();
        $sRefNr = $oPayment->formatReferenceNumber($sRefNr);
        return $sRefNr;
    }

    /**
     * Send request to PAYONE Server-API with request-type "genericpayment" and action "confirmorderreference"
     *
     * @param  PayoneMethod $oPayment payment object
     * @param  Quote        $oQuote
     * @param  string       $sWorkorderId
     * @param  string       $sAmazonReferenceId
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, Quote $oQuote, $sWorkorderId, $sAmazonReferenceId)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'cancelorderreference');

        $this->addParameter('add_paydata[amazon_reference_id]', $sAmazonReferenceId);
        $this->addParameter('add_paydata[reference]', $this->getReservedOrderId($oQuote, $oPayment));
        $this->addParameter('workorderid', $sWorkorderId);

        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid'));
        $this->addParameter('api_version', '3.10');

        $this->addParameter('clearingtype', $oPayment->getClearingtype());
        $this->addParameter('wallettype', 'AMZ');

        $this->addParameter('currency', $oQuote->getQuoteCurrencyCode());

        $this->addParameter('successurl', $this->url->getUrl('payone/amazon/loadReview?action=placeOrder'));
        $this->addParameter('errorurl', $this->url->getUrl('payone/amazon/confirmOrderError'));

        return $this->send($oPayment);
    }
}
