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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Paypal;

use Magento\Framework\View\Element\Template;
use Payone\Core\Model\Methods\PayoneMethod;
use Payone\Core\Model\PayoneConfig;

/**
 * Block class for the PayPal Express V2 button
 */
class ExpressButtonV2 extends Base
{
    /**
     * Shortcut alias
     *
     * @var string
     */
    protected $alias = 'payone.block.paypal.expressbuttonv2';

    /**
     * @var string
     */
    protected $sTemplate = 'paypal/express_buttonv2.phtml';

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * PAYONE API helper
     *
     * @var \Payone\Core\Helper\Api
     */
    protected $apiHelper;

    /**
     * Payment helper object
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Checkout\Model\Session;
     */
    protected $checkoutSession;

    /**
     * @var PayoneMethod
     */
    protected $methodInstance = null;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Locale\ResolverInterface      $localeResolver
     * @param \Payone\Core\Helper\Payment                      $paymentHelper
     * @param \Payone\Core\Helper\Api                          $apiHelper
     * @param \Magento\Payment\Helper\Data                     $dataHelper
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Payone\Core\Helper\Payment $paymentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Magento\Payment\Helper\Data $dataHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $localeResolver, $data);
        $this->paymentHelper = $paymentHelper;
        $this->apiHelper = $apiHelper;
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return PayoneMethod
     */
    public function getMethodInstance()
    {
        if ($this->methodInstance === null) {
            $this->methodInstance = $this->dataHelper->getMethodInstance(PayoneConfig::METHOD_PAYPALV2);
        }
        return $this->methodInstance;
    }

    /**
     * @return string
     */
    public function getStoreCode()
    {
        return $this->checkoutSession->getQuote()->getStore()->getCode();
    }

    /**
     * @return int|mixed
     */
    public function getQuoteId()
    {
        return $this->checkoutSession->getQuote()->getId();
    }

    /**
     * @return string
     */
    public function getButtonColor()
    {
        return $this->getMethodInstance()->getCustomConfigParam('button_color');
    }

    /**
     * @return string
     */
    public function getButtonShape()
    {
        return $this->getMethodInstance()->getCustomConfigParam('button_shape');
    }

    /**
     * @return bool
     */
    protected function showBNPLButton()
    {
        $blReturn = false;
        if ($this->paymentHelper->getConfigParam('show_bnpl_button', PayoneConfig::METHOD_PAYPALV2, 'payone_payment')) {
            $blReturn = true;
        }
        return $blReturn;
    }

    /**
     * @return string
     */
    protected function getIntent()
    {
        return "authorize"; // authorize = preauthorize // capture = authorize but Payone said to always use authorize
    }

    /**
     * @return string
     */
    protected function getCurrency()
    {
        return $this->apiHelper->getCurrencyFromQuote($this->checkoutSession->getQuote());
    }

    /**
     * @return string
     */
    protected function getMerchantId()
    {
        $sMerchantId = "3QK84QGGJE5HW"; // Default for testmode (fixed)

        $oMethodInstance = $this->getMethodInstance();
        if ($oMethodInstance && $oMethodInstance->getOperationMode() == 'live') {
            $sMerchantId = $oMethodInstance->getCustomConfigParam('merchant_id'); // Get from config for live
        }
        return $sMerchantId;
    }

    /**
     * @return string
     */
    protected function getClientId()
    {
        $sClientId = "AUn5n-4qxBUkdzQBv6f8yd8F4AWdEvV6nLzbAifDILhKGCjOS62qQLiKbUbpIKH_O2Z3OL8CvX7ucZfh"; // Default for testmode (fixed)

        $oMethodInstance = $this->getMethodInstance();
        if ($oMethodInstance && $oMethodInstance->getOperationMode() == 'live') {
            $sClientId = "AVNBj3ypjSFZ8jE7shhaY2mVydsWsSrjmHk0qJxmgJoWgHESqyoG35jLOhH3GzgEPHmw7dMFnspH6vim"; // Livemode (fixed)
        }
        return $sClientId;
    }

    /**
     * @return string
     */
    public function getJavascriptUrl()
    {
        $sUrl = "https://www.paypal.com/sdk/js?client-id=".$this->getClientId()."&merchant-id=".$this->getMerchantId()."&currency=".$this->getCurrency()."&intent=".$this->getIntent()."&locale=".$this->getLocale()."&commit=false&vault=false&disable-funding=card,sepa,bancontact";
        if ($this->showBNPLButton() === true) {
            $sUrl .= "&enable-funding=paylater";
        }
        return $sUrl;
    }
}
