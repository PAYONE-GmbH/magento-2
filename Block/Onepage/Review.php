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

namespace Payone\Core\Block\Onepage;

use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\DataObject;

/**
 * Paypal Express Onepage checkout block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Review extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Magento\Quote\Model\Quote\Address
     */
    protected $_address;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $_addressConfig;

    /**
     * Currently selected shipping rate
     *
     * @var Rate
     */
    protected $_currentShippingRate = null;

    /**
     * Paypal controller path
     *
     * @var string
     */
    protected $_controllerPath = 'payone/onepage';

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Checkout session object
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        PriceCurrencyInterface $priceCurrency,
        Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->priceCurrency = $priceCurrency;
        $this->_taxHelper = $taxHelper;
        $this->_addressConfig = $addressConfig;
        $this->checkoutSession = $checkoutSession;
        $this->getQuote(); // fill quote property
    }

    /**
     * Return checkout quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->checkoutSession->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Return quote billing address
     *
     * @return Address
     */
    public function getBillingAddress()
    {
        return $this->_quote->getBillingAddress();
    }

    /**
     * Return quote shipping address
     *
     * @return false|Address
     */
    public function getShippingAddress()
    {
        if ($this->_quote->getIsVirtual()) {
            return false;
        }
        return $this->_quote->getShippingAddress();
    }

    /**
     * Get HTML output for specified address
     *
     * @param Address $address
     * @return string
     */
    public function renderAddress(Address $address)
    {
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
        $addressData = \Magento\Framework\Convert\ConvertArray::toFlatArray($address->getData());
        return $renderer->renderArray($addressData);
    }

    /**
     * Return carrier name from config, base on carrier code
     *
     * @param string $carrierCode
     * @return string
     */
    public function getCarrierName($carrierCode)
    {
        if ($name = $this->_scopeConfig->getValue("carriers/{$carrierCode}/title", \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return $name;
        }
        return $carrierCode;
    }

    /**
     * Get either shipping rate code or empty value on error
     *
     * @param DataObject $rate
     * @return string
     */
    public function renderShippingRateValue(DataObject $rate)
    {
        if ($rate->getErrorMessage()) {
            return '';
        }
        return $rate->getCode();
    }

    /**
     * Get shipping rate code title and its price or error message
     *
     * @param DataObject $rate
     * @param string $format
     * @param string $inclTaxFormat
     * @return string
     */
    public function renderShippingRateOption(DataObject $rate, $format = '%s - %s%s', $inclTaxFormat = ' (%s %s)')
    {
        $renderedInclTax = '';
        if ($rate->getErrorMessage()) {
            $price = $rate->getErrorMessage();
        } else {
            $price = $this->_getShippingPrice(
                $rate->getPrice(),
                $this->_taxHelper->displayShippingPriceIncludingTax()
            );

            $incl = $this->_getShippingPrice($rate->getPrice(), true);
            if ($incl != $price && $this->_taxHelper->displayShippingBothPrices()) {
                $renderedInclTax = sprintf($inclTaxFormat, $this->escapeHtml(__('Incl. Tax')), $incl);
            }
        }
        return sprintf($format, $this->escapeHtml($rate->getMethodTitle()), $price, $renderedInclTax);
    }

    /**
     * Getter for current shipping rate
     *
     * @return Rate
     */
    public function getCurrentShippingRate()
    {
        return $this->_currentShippingRate;
    }

    /**
     * Get quote email
     *
     * @return string
     */
    public function getEmail()
    {
        $billingAddress = $this->getBillingAddress();
        return $billingAddress ? $billingAddress->getEmail() : '';
    }

    /**
     * Return formatted shipping price
     *
     * @param float $price
     * @param bool $isInclTax
     * @return string
     */
    protected function _getShippingPrice($price, $isInclTax)
    {
        return $this->_formatPrice($this->_taxHelper->getShippingPrice($price, $isInclTax, $this->_address));
    }

    /**
     * Format price base on store convert price method
     *
     * @param float $price
     * @return string
     */
    protected function _formatPrice($price)
    {
        return $this->priceCurrency->convertAndFormat(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->_quote->getStore()
        );
    }

    /**
     * Retrieve payment method and assign additional template values
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $methodInstance = $this->_quote->getPayment()->getMethodInstance();
        $this->setPaymentMethodTitle($methodInstance->getTitle());

        $this->setShippingRateRequired(true);
        if ($this->_quote->getIsVirtual()) {
            $this->setShippingRateRequired(false);
        } else {
            // prepare shipping rates
            $this->_address = $this->_quote->getShippingAddress();
            $groups = $this->_address->getGroupedAllShippingRates();
            if ($groups && $this->_address) {
                $this->setShippingRateGroups($groups);
                // determine current selected code & name
                foreach ($groups as $code => $rates) {
                    foreach ($rates as $rate) {
                        if ($this->_address->getShippingMethod() == $rate->getCode()) {
                            $this->_currentShippingRate = $rate;
                            break 2;
                        }
                    }
                }
            }

            // misc shipping parameters
            $this->setShippingMethodSubmitUrl(
                $this->getUrl("{$this->_controllerPath}/review", ['_secure' => true])
            );
        }

        $this->setEditUrl(
            $this->getUrl("{$this->_controllerPath}/edit")
        )->setPlaceOrderUrl(
            $this->getUrl("{$this->_controllerPath}/placeOrder", ['_secure' => true])
        );

        return parent::_beforeToHtml();
    }
}
