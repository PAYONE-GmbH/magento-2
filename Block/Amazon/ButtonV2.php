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

namespace Payone\Core\Block\Amazon;

use Magento\Framework\View\Element\Template;
use Payone\Core\Model\Methods\AmazonPayV2;
use Payone\Core\Model\PayoneConfig;

/**
 * Block class for the Amazon Pay V2 button
 */
class ButtonV2 extends Template implements \Magento\Catalog\Block\ShortcutInterface
{
    /**
     * Shortcut alias
     *
     * @var string
     */
    protected $alias = 'payone.block.amazon.buttonv2';

    /**
     * @var \Payone\Core\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Payone\Core\Model\Methods\AmazonPayV2
     */
    protected $amazonPayment;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Payone\Core\Helper\Api                          $apiHelper
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Payone\Core\Model\Methods\AmazonPayV2           $amazonPayment
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Payone\Core\Helper\Api $apiHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Methods\AmazonPayV2 $amazonPayment,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiHelper = $apiHelper;
        $this->checkoutSession = $checkoutSession;
        $this->amazonPayment = $amazonPayment;
        $this->setTemplate('amazon/buttonv2.phtml');
    }

    /**
     * Get shortcut alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param  string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getQuoteId()
    {
        return $this->getQuote()->getId();
    }

    /**
     * @return string
     */
    public function getStoreCode()
    {
        return $this->getQuote()->getStore()->getCode();
    }

    /**
     * @return string
     */
    public function getButtonId()
    {
        $buttonId = "AmazonPayButton";
        if (strpos($this->getName(), "checkout.cart.shortcut.buttons") !== false) {
            $buttonId = "AmazonPayButtonBasket";
        } elseif (strpos($this->getName(), "shortcutbuttons") !== false) {
            $buttonId = "AmazonPayButtonMiniBasket";
        }
        return $buttonId;
    }

    /**
     * @return mixed
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * @return string
     */
    public function getPublicKeyId()
    {
        return AmazonPayV2::BUTTON_PUBLIC_KEY;
    }

    /**
     * Get Amazon merchant id from config
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->amazonPayment->getMerchantId();
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->amazonPayment->useSandbox();
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->apiHelper->getCurrencyFromQuote($this->getQuote());
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->apiHelper->getQuoteAmount($this->getQuote());
    }

    /**
     * @return string
     */
    public function getProductType()
    {
        $oQuote = $this->getQuote();
        if ($oQuote->isVirtual() === true) {
            return "PayOnly";
        }
        return "PayAndShip";
        /**
         * 'PayAndShip' - Offer checkout using buyer's Amazon wallet and address book. Select this product type if you need the buyer's shipping details
         * 'PayOnly' - Offer checkout using only the buyer's Amazon wallet. Select this product type if you do not need the buyer's shipping details
         * 'SignIn' - Offer Amazon Sign-in. Select this product type if you need buyer details before the buyer starts Amazon Pay checkout. See Amazon Sign-in for more information.
         * Default value: 'PayAndShip'
         */
    }

    /**
     * @return string
     */
    public function getPlacement()
    {
        if ($this->getButtonId() == "AmazonPayButtonBasket") {
            return "Cart";
        }
        return "Home";
        /**
         * 'Home' - Initial or main page
         * 'Product' - Product details page
         * 'Cart' - Cart review page before buyer starts checkout
         * 'Checkout' - Any page after buyer starts checkout
         * 'Other' - Any page that doesn't fit the previous descriptions
         */
    }

    /**
     * Get Amazon button color from config
     *
     * @return string
     */
    public function getButtonColor()
    {
        return $this->amazonPayment->getButtonColor();
    }

    /**
     * Get Amazon button language from config
     *
     * @return string
     */
    public function getButtonLanguage()
    {
        return $this->amazonPayment->getButtonLanguage();
    }
}
