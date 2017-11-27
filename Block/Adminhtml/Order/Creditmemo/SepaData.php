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

namespace Payone\Core\Block\Adminhtml\Order\Creditmemo;

use Payone\Core\Model\Methods\PayoneMethod;

/**
 * Class for SEPA bankdata inputs in creditmemo
 */
class SepaData extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve credit memo model instance
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->_coreRegistry->registry('current_creditmemo');
    }

    /**
     * Retrieve invoice order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getCreditmemo()->getOrder();
    }

    /**
     * Return if the current order is a PAYONE order
     *
     * @return bool
     */
    public function showPayoneSepaDataFields()
    {
        $oMethodInstance = $this->getOrder()->getPayment()->getMethodInstance();
        if ($oMethodInstance instanceof PayoneMethod && $oMethodInstance->needsSepaDataOnDebit()) {
            return true;
        }
        return false;
    }

    /**
     * Get IBAN from order object if existing
     *
     * @return string
     */
    public function getPrefilledIban()
    {
        return $this->getOrder()->getPayoneRefundIban();
    }

    /**
     * Get BIC from order object if existing
     *
     * @return string
     */
    public function getPrefilledBic()
    {
        return $this->getOrder()->getPayoneRefundBic();
    }
}
