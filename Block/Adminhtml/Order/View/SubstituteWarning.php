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

namespace Payone\Core\Block\Adminhtml\Order\View;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\UrlInterface;
use Payone\Core\Helper\Database;

class SubstituteWarning extends Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var Database
     */
    protected $databaseHelper;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * Url builder object
     *
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Order
     */
    protected $oSubstituteOrder = null;

    /**
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Database $databaseHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        Database $databaseHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->databaseHelper = $databaseHelper;
        $this->orderFactory = $orderFactory;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('sales_order');
    }

    /**
     * Checks if the current order is a substitute order
     *
     * @return bool
     */
    public function isSubstituteOrder()
    {
        $oOrder = $this->getOrder();
        if (!empty($oOrder->getPayoneCancelSubstituteIncrementId())) {
            return true;
        }
        return false;
    }

    /**
     * Check if the current order has a substitute order
     *
     * @return bool
     */
    public function hasSubstituteOrder()
    {
        if ($this->getOrder()->getStatus() == Order::STATE_CANCELED && !empty($this->getSubstituteOrder())) {
            return true;
        }
        return false;
    }

    /**
     * @return Order|false
     */
    protected function getSubstituteOrder()
    {
        if ($this->oSubstituteOrder === null) {
            $this->oSubstituteOrder = false;

            $sIncrementId = $this->databaseHelper->getSubstituteOrderIncrementId($this->getOrder()->getIncrementId());
            if (!empty($sIncrementId)) {
                $oOrder = $this->orderFactory->create()->loadByIncrementId($sIncrementId);
                if ($oOrder && $oOrder->getId()) {
                    $this->oSubstituteOrder = $oOrder;
                }
            }
        }
        return $this->oSubstituteOrder;
    }

    protected function getOrigOrder()
    {
        $oOrder = $this->orderFactory->create()->loadByIncrementId($this->getOrder()->getPayoneCancelSubstituteIncrementId());
        if ($oOrder && $oOrder->getId()) {
            return $oOrder;
        }
        return false;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->hasSubstituteOrder() || $this->isSubstituteOrder()) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Returns backend order url
     *
     * @param  string $sOrderId
     * @return string
     */
    protected function getViewBackendOrderUrl($sOrderId)
    {
        return $this->urlBuilder->getUrl('sales/order/view', [
            'order_id' => $sOrderId
        ]);
    }

    /**
     * Returns URL to original order
     *
     * @return string
     */
    public function getOrigOrderBackendUrl()
    {
        $oOrigOrder = $this->getOrigOrder();
        if ($oOrigOrder) {
            return $this->getViewBackendOrderUrl($oOrigOrder->getId());
        }
        return false;
    }

    /**
     * @return string|false
     */
    public function getSubstituteOrderBackendUrl()
    {
        $oSubstituteOrder = $this->getSubstituteOrder();
        if (!empty($oSubstituteOrder)) {
            return $this->getViewBackendOrderUrl($oSubstituteOrder->getId());
        }
        return false;
    }

    /**
     * @return string|false
     */
    public function getSubstituteOrderIncrementNr()
    {
        $oSubstituteOrder = $this->getSubstituteOrder();
        if (!empty($oSubstituteOrder)) {
            return $oSubstituteOrder->getIncrementId();
        }
        return false;
    }
}