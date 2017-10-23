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
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context                    $context
     * @param \Magento\Framework\Data\Form\Element\Factory               $elementFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\Collection $orderStatus
     * @param \Payone\Core\Model\Source\TransactionStatus                $transactionStatus
     * @param array                                                      $data
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
        $this->addColumn('txaction', ['label' => __('Transactionstatus-message')]); // set column name for txaction
        $this->addColumn('state_status', ['label' => __('Magento-status')]); // set column name for state_status
        $this->addAfter = false; // dont add "add after" button
        $this->addButtonLabel = __('Add Statusmapping'); // set the label text of the button
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
            $aOptions = $this->transactionStatus->toOptionArray(); // add transction status action options to dropdown
        } elseif ($columnName == 'state_status' && isset($this->_columns[$columnName])) {
            $aOptions = $this->orderStatus->toOptionArray(); // add state_status options to dropdown
        } else {
            return parent::renderCellTemplate($columnName);
        }

        $oElement = $this->elementFactory->create('select');
        $oElement->setForm($this->getForm());
        $oElement->setName($this->_getCellInputElementName($columnName));
        $oElement->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName));
        $oElement->setValues($aOptions);
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
        $result = [];
        /** @var \Magento\Framework\Data\Form\Element\AbstractElement */
        $element = $this->getElement();
        $aValue = $element->getValue(); // get values
        if (is_array($aValue) === false) { // no array given? -> value from config.xml
            $aValue = json_decode($aValue, true); // convert string to array
        }
        if ($aValue && is_array($aValue)) {
            foreach ($aValue as $rowId => $row) {
                $rowColumnValues = [];
                foreach ($row as $key => $value) {
                    $row[$key] = $value;
                    $rowColumnValues[$this->_getCellInputElementId($rowId, $key)] = $row[$key]; // add value the row
                }
                $row['_id'] = $rowId;
                $row['column_values'] = $rowColumnValues;
                $result[$rowId] = new \Magento\Framework\DataObject($row);
                $this->_prepareArrayRow($result[$rowId]);
            }
        }
        return $result;
    }
}
