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
 * Block class for status-mapping grid-element
 */
class StatusMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Element factory
     *
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * List of all possible OrderStatus types
     *
     * @var \Magento\Sales\Model\Config\Source\Order\Status
     */
    protected $orderStatus;

    /**
     * List of all possible TransactionStatus types
     *
     * @var \Payone\Core\Model\Source\TransactionStatus
     */
    protected $transactionStatus;
    
    /**
     * Rows cache
     *
     * @var array|null
     */
    private $_arrayRowsCache;

    /**
     * Constructor
     *
     * @param  \Magento\Backend\Block\Template\Context         $context
     * @param  \Magento\Framework\Data\Form\Element\Factory    $elementFactory
     * @param  \Magento\Sales\Model\Config\Source\Order\Status $orderStatus
     * @param  \Payone\Core\Model\Source\TransactionStatus     $transactionStatus
     * @param  array                                           $data
     * @return void
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\Collection $orderStatus,
        \Payone\Core\Model\Source\TransactionStatus $transactionStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory = $elementFactory;
        $this->orderStatus = $orderStatus;
        $this->transactionStatus = $transactionStatus;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('txaction', ['label' => __('Transactionstatus-message')]);
        $this->addColumn('state_status', ['label' => __('Magento-status')]);
        $this->addAfter = false;
        $this->addButtonLabel = __('Add Statusmapping');
        parent::_construct();
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param  string $columnName
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'txaction' && isset($this->_columns[$columnName])) {
            $aOptions = $this->transactionStatus->toOptionArray();
        } elseif ($columnName == 'state_status' && isset($this->_columns[$columnName])) {
            $aOptions = $this->orderStatus->toOptionArray();
        } else {
            return parent::renderCellTemplate($columnName);
        }

        $oElement = $this->elementFactory->create('select');
        $oElement->setForm(
            $this->getForm()
        )->setName(
            $this->_getCellInputElementName($columnName)
        )->setHtmlId(
            $this->_getCellInputElementId('<%- _id %>', $columnName)
        )->setValues(
            $aOptions
        );
        return str_replace("\n", '', $oElement->getElementHtml());
    }
    
    /**
     * Obtain existing data from form element
     *
     * Each row will be instance of \Magento\Framework\DataObject
     *
     * @return array
     */
    public function getArrayRows()
    {
        if (null !== $this->_arrayRowsCache) {
            return $this->_arrayRowsCache;
        }
        $result = [];
        /** @var \Magento\Framework\Data\Form\Element\AbstractElement */
        $element = $this->getElement();
        $aValue = $element->getValue();
        if (!is_array($aValue)) {
            $aValue = unserialize($aValue);
        }
        
        if ($aValue && is_array($aValue)) {
            foreach ($aValue as $rowId => $row) {
                $rowColumnValues = [];
                foreach ($row as $key => $value) {
                    $row[$key] = $value;
                    $rowColumnValues[$this->_getCellInputElementId($rowId, $key)] = $row[$key];
                }
                $row['_id'] = $rowId;
                $row['column_values'] = $rowColumnValues;
                $result[$rowId] = new \Magento\Framework\DataObject($row);
                $this->_prepareArrayRow($result[$rowId]);
            }
        }
        $this->_arrayRowsCache = $result;
        return $this->_arrayRowsCache;
    }

    /**
     * Get the grid and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->_toHtml();
        $this->_arrayRowsCache = null;
        // doh, the object is used as singleton!
        return $html;
    }
}
