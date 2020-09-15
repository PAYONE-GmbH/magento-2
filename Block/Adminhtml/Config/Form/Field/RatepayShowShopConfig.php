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

namespace Payone\Core\Block\Adminhtml\Config\Form\Field;

/**
 * Admin-block for displaying Ratepay shop config
 */
class RatepayShowShopConfig extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Payone_Core::system/config/form/field/ratepay_show_shop_config.phtml';

    /**
     * @var \Payone\Core\Helper\Ratepay
     */
    protected $ratepayHelper;

    /**
     * Ratepay profile resource model
     *
     * @var \Payone\Core\Model\ResourceModel\RatepayProfileConfig
     */
    protected $ratepayProfileResource;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context               $context
     * @param \Payone\Core\Helper\Ratepay                           $ratepayHelper
     * @param \Payone\Core\Model\ResourceModel\RatepayProfileConfig $ratepayProfileResource
     * @param array                                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Payone\Core\Helper\Ratepay $ratepayHelper,
        \Payone\Core\Model\ResourceModel\RatepayProfileConfig $ratepayProfileResource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->ratepayHelper = $ratepayHelper;
        $this->ratepayProfileResource = $ratepayProfileResource;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('txaction', ['label' => __('Transactionstatus-message')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Minimum Qty');
        parent::_construct();
    }

    /**
     * Returns current payment method
     *
     * @return string|false
     */
    protected function getCurrentPaymentMethod()
    {
        $oElement = $this->getDataByKey('element');
        if ($oElement) {
            $aOrigData = $oElement->getOriginalData();
            if (isset($aOrigData['path'])) {
                return str_replace('payone_payment/', '', $aOrigData['path']);
            }
        }
        return false;
    }

    /**
     * Returns Ratepay shop configurations for current payment method
     *
     * @return array
     */
    public function getRatepayShopConfig()
    {
        $sCurrentPaymentMethod = $this->getCurrentPaymentMethod();

        $aShopIds = $this->ratepayHelper->getRatepayShopConfigIdsByPaymentMethod($sCurrentPaymentMethod);
        if (empty($aShopIds)) {
            return [];
        }
        return $this->ratepayProfileResource->getProfileConfigsByIds($aShopIds);
    }
}
