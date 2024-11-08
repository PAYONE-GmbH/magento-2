<?php

namespace Payone\Core\Model\Api\Request\Genericpayment;

use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Model\Methods\PaypalV2;
use Payone\Core\Model\PayoneConfig;
use Magento\Quote\Model\Quote;

class UpdateOrder extends Base
{
    /**
     * Invoice generator
     *
     * @var \Payone\Core\Model\Api\Invoice
     */
    protected $invoiceGenerator;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Helper\Toolkit             $toolkitHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Payone\Core\Model\Api\Invoice          $invoiceGenerator
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Payone\Core\Model\Api\Invoice $invoiceGenerator
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $toolkitHelper, $apiLog, $customerHelper);
        $this->invoiceGenerator = $invoiceGenerator;
    }

    /**
     * Send request to PAYONE Server-API with
     * request-type "genericpayment"
     *
     * @param Quote        $oQuote
     * @param PayoneMethod $oPayment
     * @param string|bool  $sWorkorderId
     * @return array Response
     */
    public function sendRequest(Quote $oQuote, PayoneMethod $oPayment, $sWorkorderId = false)
    {
        $this->addParameter('request', 'genericpayment');
        $this->addParameter('add_paydata[action]', 'update_order');
        $this->addParameter('workorderid', $sWorkorderId);
        $this->addParameter('mode', $oPayment->getOperationMode());
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('clearingtype', $oPayment->getClearingtype());
        $this->addParameter('wallettype', $oPayment->getWallettype());

        $this->addParameter('amount', number_format($this->apiHelper->getQuoteAmount($oQuote), 2, '.', '') * 100); // add price to request
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromQuote($oQuote)); // add currency to request

        $this->addParameter('add_paydata[payment_action]', $oPayment->getAuthorizationMode() == PayoneConfig::REQUEST_TYPE_AUTHORIZATION ? 'Capture' : 'Authorize'); # Is either Capture (for Authorization call) or Authorize (for preauthorization call)
        $this->addParameter('add_paydata[request_id]', 'TODO');

        $this->addRedirectUrls($oPayment);

        $this->invoiceGenerator->addProductInfo($this, $oQuote);

        return $this->send($oPayment);
    }
}