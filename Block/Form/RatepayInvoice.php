<?php

namespace Payone\Core\Block\Form;

use Payone\Core\Model\PayoneConfig;

class RatepayInvoice extends Base
{
    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $orderCreate;

    /**
     * @var \Payone\Core\Helper\Ratepay
     */
    protected $ratepayHelper;

    /**
     * @var array|null
     */
    protected $ratepayConfig = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param \Payone\Core\Helper\Ratepay $ratepayHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Payone\Core\Helper\Ratepay $ratepayHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderCreate = $orderCreate;
        $this->ratepayHelper = $ratepayHelper;
    }

    /**
     * Retrieve create order model object
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->orderCreate->getQuote();
    }

    /**
     * Tries to determine a matching ratepay configuration
     *
     * @return array
     */
    public function getRatepayConfig()
    {
        if ($this->ratepayConfig === null) {
            $this->ratepayConfig = $this->ratepayHelper->getRatepaySingleConfig(PayoneConfig::METHOD_RATEPAY_INVOICE, $this->getQuote());
        }
        return $this->ratepayConfig;
    }

    /**
     * Returns snippet id from config
     *
     * @return string
     */
    public function getDevicefingerprintSnippetId()
    {
        return $this->ratepayHelper->getConfigParam('devicefingerprint_snippet_id', 'ratepay', 'payone_misc');
    }

    /**
     * Returns token generated by Ratepay helper
     *
     * @return string
     */
    public function getDevicefingerprintToken()
    {
        return $this->ratepayHelper->getRatepayDeviceFingerprintToken();
    }

    /**
     * Returns if birthday has to be entered
     *
     * @return bool
     */
    public function isBirthdayNeeded()
    {
        if ($this->isB2BMode() === true) {
            return false;
        }
        return true;
    }

    /**
     * Return if customer has entered a company name in his billing address
     *
     * @return bool
     */
    public function isB2BMode()
    {
        $billingAddress = $this->getQuote()->getBillingAddress();
        if ($billingAddress && !empty($billingAddress->getCompany())) {
            return true;
        }
        return false;
    }

    /**
     * Return if ratepay config allows B2B mode
     *
     * @return bool
     */
    public function isB2BModeAllowed()
    {
        $aConfig = $this->getRatepayConfig();
        if (isset($aConfig['b2bAllowed'])) {
            return (bool)$aConfig['b2bAllowed'];
        }
        return false;
    }

    /**
     * Return if ratepay config allows differing delivery addresses
     *
     * @return bool
     */
    public function isDifferingDeliveryAddressAllowed()
    {
        $aConfig = $this->getRatepayConfig();
        if (isset($aConfig['differentAddressAllowed'])) {
            return (bool)$aConfig['differentAddressAllowed'];
        }
        return false;
    }

    /**
     * Returns if billing address is different as shipping address
     *
     * @return true
     */
    public function hasDifferingDeliveryAddress()
    {
        return !$this->getQuote()->getShippingAddress()->getSameAsBilling();
    }

    /**
     * Returns the customers birthday if known
     *
     * @return string
     */
    public function getBirthday()
    {
        return $this->getQuote()->getCustomer()->getDob();
    }

    /**
     * Returns a part of the birthday (day, month or year)
     *
     * @param  string $sPart
     * @return string
     */
    public function getBirthdayPart($sPart)
    {
        $sBirthday = $this->getBirthday();
        if (!empty($sBirthday)) {
            $timestamp = strtotime($sBirthday);
            return date($sPart, $timestamp);
        }
        return '';
    }

    /**
     * Returns if the telephone number has to be entered
     *
     * @return bool
     */
    public function isTelephoneNeeded()
    {
        $billingAddress = $this->getQuote()->getBillingAddress();
        if ($billingAddress && !empty($billingAddress->getTelephone())) {
            return false;
        }
        return true;
    }
}
