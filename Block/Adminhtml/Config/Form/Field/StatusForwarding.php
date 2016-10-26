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
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Adminhtml\Config\Form\Field;

/**
 * Block class for status-forwarding grid-element
 */
class StatusForwarding extends \Payone\Core\Block\Adminhtml\Config\Form\Field\FieldArray\Multiselect
{
    /**
     * Element factory
     *
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * List of all possible TransactionStatus types
     *
     * @var \Payone\Core\Model\Source\TransactionStatus
     */
    protected $transactionStatus;

    /**
     * Constructor
     *
     * @param  \Magento\Backend\Block\Template\Context      $context
     * @param  \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param  \Payone\Core\Model\Source\TransactionStatus  $transactionStatus
     * @param  array                                        $data
     * @return void
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Payone\Core\Model\Source\TransactionStatus $transactionStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory = $elementFactory;
        $this->transactionStatus = $transactionStatus;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('txaction', ['label' => __('Status')]);
        $this->addColumn('url', ['label' => __('URL')]);
        $this->addColumn('timeout', ['label' => __('Timeout')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param  string $sColumnName
     * @return string
     */
    public function renderCellTemplate($sColumnName)
    {
        if ($sColumnName == 'txaction' && isset($this->_columns[$sColumnName])) {
            $aOptions = $this->transactionStatus->toOptionArray();
            $oElement = $this->elementFactory->create('multiselect');
            $oElement->setForm($this->getForm());
            $oElement->setName($this->_getCellInputElementName($sColumnName));
            $oElement->setHtmlId($this->_getCellInputElementId('<%- _id %>', $sColumnName));
            $oElement->setValues($aOptions);
            return str_replace("\n", '', $oElement->getElementHtml());
        }
        return parent::renderCellTemplate($sColumnName);
    }
}
