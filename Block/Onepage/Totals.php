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

namespace Payone\Core\Block\Onepage;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Block\Cart\Totals as CoreTotals;

/**
 * Block class for review page totals
 */
class Totals extends CoreTotals
{
    /**
     * Payone base helper
     *
     * @var \Payone\Core\Helper\Base
     */
    protected $baseHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Payone\Core\Helper\Base $baseHelper
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Config $salesConfig,
        \Payone\Core\Helper\Base $baseHelper,
        array $layoutProcessors = [],
        array $data = []
    ) {
        $this->_salesConfig = $salesConfig;
        parent::__construct($context, $customerSession, $checkoutSession, $salesConfig, $layoutProcessors, $data);
        $this->layoutProcessors = $layoutProcessors;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Check if we have display grand total in base currency
     *
     * @return bool
     */
    public function needDisplayBaseGrandtotal()
    {
        $quote = $this->getQuote();
        if ($quote->getBaseCurrencyCode() != $quote->getQuoteCurrencyCode() && $this->baseHelper->getConfigParam('currency') == 'base') {
            return true;
        }
        return false;
    }
}
