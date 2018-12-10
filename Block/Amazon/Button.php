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

namespace Payone\Core\Block\Amazon;

use Magento\Framework\View\Element\Template;
use Payone\Core\Model\PayoneConfig;

/**
 * Block class for the PayPal Express button
 */
class Button extends Template implements \Magento\Catalog\Block\ShortcutInterface
{
    /**
     * Shortcut alias
     *
     * @var string
     */
    protected $alias = 'payone.block.amazon.button';

    /**
     * @var int $counter
     */
    public static $counter = 0;

    /**
     * @var \Payone\Core\Helper\Base
     */
    protected $baseHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Payone\Core\Helper\Base                         $baseHelper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Payone\Core\Helper\Base $baseHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->baseHelper = $baseHelper;
        $this->setTemplate('amazon/button.phtml');
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
     * Get Amazon client id from config
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->baseHelper->getConfigParam('client_id', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment');
    }

    /**
     * Get Amazon seller id from config
     *
     * @return string
     */
    public function getSellerId()
    {
        return $this->baseHelper->getConfigParam('seller_id', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment');
    }

    /**
     * Get Amazon button type from config
     *
     * @return string
     */
    public function getButtonType()
    {
        return $this->baseHelper->getConfigParam('button_type', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment');
    }

    /**
     * Get Amazon button color from config
     *
     * @return string
     */
    public function getButtonColor()
    {
        return $this->baseHelper->getConfigParam('button_color', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment');
    }

    /**
     * Get Amazon button language from config
     *
     * @return string
     */
    public function getButtonLanguage()
    {
        return $this->baseHelper->getConfigParam('button_language', PayoneConfig::METHOD_AMAZONPAY, 'payone_payment');
    }

    /**
     * Get redirect url of the checkout controller
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getUrl("payone/onepage/amazon", ['_secure' => true]);
    }

    /**
     * Returns current counter
     *
     * @return int
     */
    public function getCounter()
    {
        return ++$this::$counter;
    }
}
