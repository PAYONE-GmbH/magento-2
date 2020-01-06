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

use Magento\Framework\View\Element\Template;
use Payone\Core\Model\PayoneConfig;

/**
 * Block class for Amazon Pay checkout
 */
class Amazon extends Template
{
    /**
     * Checkout session object
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Payone\Core\Helper\Payment                      $paymentHelper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        \Payone\Core\Helper\Payment $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Get Amazon client id from config
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->paymentHelper->getConfigParam('client_id', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment');
    }

    /**
     * Get Amazon seller id from config
     *
     * @return string
     */
    public function getSellerId()
    {
        return $this->paymentHelper->getConfigParam('seller_id', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment');
    }

    /**
     * Get redirect url of the checkout controller
     *
     * @return string
     */
    public function getCartUrl()
    {
        return $this->_urlBuilder->getUrl("checkout/cart", ['_secure' => true]);
    }

    /**
     * Get url of the loadReview ajax controller
     *
     * @return string
     */
    public function getLoadReviewUrl()
    {
        return $this->_urlBuilder->getUrl("payone/amazon/loadReview", ['_secure' => true]);
    }

    /**
     * Returns error url
     *
     * @return string
     */
    public function getErrorUrl()
    {
        return $this->_urlBuilder->getUrl('payone/amazon/confirmOrderError');
    }

    /**
     * Get amazon widget url
     *
     * @return string
     */
    public function getWidgetUrl()
    {
        return $this->paymentHelper->getAmazonPayWidgetUrl();
    }

    /**
     * Check if request param invalidPayment is set
     *
     * @return bool
     */
    public function isInvalidPaymentTriggered()
    {
        if ($this->getRequest()->getParam('invalidPayment')) {
            return true;
        }
        return false;
    }

    /**
     * Returns amazon order reference id
     *
     * @return string
     */
    public function getOrderReferenceId()
    {
        return $this->checkoutSession->getAmazonReferenceId();
    }
}
