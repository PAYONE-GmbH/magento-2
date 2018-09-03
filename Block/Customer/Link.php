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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Customer;

use Payone\Core\Model\PayoneConfig;

/**
 * Block class for deactivating display of the creditcard management
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * PAYONE base helper
     *
     * @var \Payone\Core\Helper\Base
     */
    protected $baseHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Payone\Core\Helper\Base $baseHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Payone\Core\Helper\Base $baseHelper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->baseHelper = $baseHelper;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function toHtml()
    {
        if ((bool)$this->baseHelper->getConfigParam('save_data_enabled', PayoneConfig::METHOD_CREDITCARD, 'payone_payment') === true) {
            return parent::toHtml();
        }
        return '';
    }
}
