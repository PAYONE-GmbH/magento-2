<?php
/**
 * Created by PhpStorm.
 * User: Robert
 * Date: 10.07.2019
 * Time: 12:03
 */

namespace Payone\Core\Model\Api\Request;

use Magento\Customer\Model\Customer;
use Magento\Quote\Model\Quote;

class PaydirektAgreement extends AddressRequest
{
    /**
     * URL helper
     *
     * @var \Magento\Framework\Url
     */
    protected $url;

    /**
     * PAYONE paydirekt payment model
     *
     * @var \Payone\Core\Model\Methods\Paydirekt
     */
    protected $paydirekt;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Helper\Toolkit             $toolkitHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Magento\Framework\Url                  $url
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Helper\Toolkit $toolkitHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Magento\Framework\Url $url,
        \Payone\Core\Model\Methods\Paydirekt $paydirekt
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $toolkitHelper, $apiLog, $customerHelper);
        $this->url = $url;
        $this->paydirekt = $paydirekt;
    }

    /**
     * Send paydirekt oneKlick agreement request
     *
     * @param  Customer $oCustomer
     * @param  Quote    $oQuote
     * @return array
     * @throws \Exception
     */
    public function sendAgreementRequest(Customer $oCustomer, Quote $oQuote)
    {
        $this->initRequest(); // reinitialize

        $this->addParameter('request', 'preauthorization'); // add request type
        $this->addParameter('mode', $this->paydirekt->getOperationMode());
        $this->addParameter('clearingtype', $this->paydirekt->getClearingtype());
        $this->addParameter('wallettype', 'PDT');
        $this->addParameter('currency', $oQuote->getQuoteCurrencyCode());
        $this->addParameter('amount', '1');
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // add sub account id
        if ($this->shopHelper->getConfigParam('transmit_customerid') == '1') {
            $this->addParameter('customerid', $oCustomer->getId());
        }
        $this->addParameter('customer_is_present', 'yes');
        $this->addParameter('reference', 'custnr_'.$oCustomer->getId());
        $this->addParameter('add_paydata[device_id]', '1');
        $this->addParameter('add_paydata[device_name]', 'Test');
        $this->addParameter('api_version', '3.10');
        $this->addParameter('recurrence', 'oneclick');
        $sIp = $this->environmentHelper->getRemoteIp();
        if ($sIp != '') {
            $this->addParameter('ip', $sIp);
        }

        $oBillingAddress = $oCustomer->getDefaultBillingAddress();
        if (!$oBillingAddress) {
            $oBillingAddress = $oQuote->getBillingAddress();
        }

        $this->addAddress($oBillingAddress);
        $this->addUserDataParameters($oBillingAddress, $this->paydirekt, $oCustomer->getGender(), $oCustomer->getEmail(), $oCustomer->getDob());
        $this->addAgreementRedirectUrls();

        $aResponse = $this->send();

        return $aResponse;
    }

    /**
     * Add the redirect urls to the request
     *
     * @return void
     */
    protected function addAgreementRedirectUrls()
    {
        $this->addParameter('successurl', $this->url->getUrl('payone/paydirekt/agreement?return=1'));
        $this->addParameter('errorurl', $this->url->getUrl('payone/onepage/cancel?error=1'));
        $this->addParameter('backurl', $this->url->getUrl('payone/onepage/cancel'));
    }
}
