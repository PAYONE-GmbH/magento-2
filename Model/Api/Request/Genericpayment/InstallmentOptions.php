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
 * @copyright 2003 - 2022 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Api\Request\Genericpayment;

use Magento\Quote\Model\Quote;
use Payone\Core\Model\Methods\PayoneMethod;

/**
 * Class for the PAYONE Server API request genericpayment - "installment_options"
 */
class InstallmentOptions extends Base
{
    /**
     * Payment helper object
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Helper\Toolkit             $toolkitHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Magento\Payment\Helper\Data            $dataHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Magento\Payment\Helper\Data $dataHelper
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $toolkitHelper, $apiLog, $customerHelper);
        $this->dataHelper = $dataHelper;
    }

    /**
     * Send request to PAYONE Server-API with
     * request-type "genericpayment"
     *
     * @param Quote         $oQuote
     * @param string        $sPaymentCode
     * @return array Response
     */
    public function sendRequest(Quote $oQuote, $sPaymentCode)
    {
        /** @var PayoneMethod $oPayment */
        $oPayment = $this->dataHelper->getMethodInstance($sPaymentCode);

        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'installment_options');
        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('clearingtype', 'fnc');
        $this->addParameter('financingtype', $oPayment->getSubType());

        $this->addParameter('amount', number_format($this->apiHelper->getQuoteAmount($oQuote), 2, '.', '') * 100); // add price to request
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromQuote($oQuote)); // add currency to request

        return $this->send($oPayment);
    }
}
