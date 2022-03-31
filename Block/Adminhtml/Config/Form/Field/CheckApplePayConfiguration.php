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
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Adminhtml\Config\Form\Field;

use Payone\Core\Model\PayoneConfig;

/**
 * Admin-block for the Ratepay refresh profile button
 */
class CheckApplePayConfiguration extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Payone\Core\Helper\ApplePay
     */
    protected $applePayHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context   $context
     * @param \Payone\Core\Helper\ApplePay              $applePayHelper
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Payone\Core\Helper\ApplePay $applePayHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->applePayHelper = $applePayHelper;
    }

    /**
     * Set template to itself
     *
     * @return \Payone\Core\Block\Adminhtml\Config\Form\Field\CheckApplePayConfiguration
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/form/field/check_applepay_configuration.phtml');
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData(['html_id' => $element->getHtmlId()]);

        return $this->_toHtml();
    }

    /**
     * Checks if all needed configuration fields are correctly configured
     *
     * @return bool
     */
    public function isConfigurationComplete()
    {
        return $this->applePayHelper->isConfigurationComplete();
    }

    /**
     * Check if merchant id configured
     *
     * @return bool
     */
    public function hasMerchantId()
    {
        return $this->applePayHelper->hasMerchantId();
    }

    /**
     * Check if certificate file is configured and exists
     *
     * @return bool
     */
    public function hasCertificateFile()
    {
        return $this->applePayHelper->hasCertificateFile();
    }

    /**
     * Check if private key file is configured and exists
     *
     * @return bool
     */
    public function hasPrivateKeyFile()
    {
        return $this->applePayHelper->hasPrivateKeyFile();
    }
}
