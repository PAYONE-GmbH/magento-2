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
 * Block class for person-status-mapping grid-element
 */
class PersonStatusMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Element factory
     *
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * Person status source class
     *
     * @var \Payone\Core\Model\Source\PersonStatus
     */
    protected $personStatus;

    /**
     * Credit score source class
     *
     * @var \Payone\Core\Model\Source\CreditScore
     */
    protected $creditScore;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context      $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Payone\Core\Model\Source\PersonStatus       $personStatus
     * @param \Payone\Core\Model\Source\CreditScore        $creditScore
     * @param array                                        $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Payone\Core\Model\Source\PersonStatus $personStatus,
        \Payone\Core\Model\Source\CreditScore $creditScore,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory = $elementFactory;
        $this->personStatus = $personStatus;
        $this->creditScore = $creditScore;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('personstatus', ['label' => __('Personstatus')]);
        $this->addColumn('score', ['label' => __('Score')]);
        $this->addAfter = false;
        $this->addButtonLabel = __('Add Personstatus Mapping');
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
        if ($columnName == 'personstatus' && isset($this->_columns[$columnName])) {
            $aOptions = $this->personStatus->toOptionArray();
        } elseif ($columnName == 'score' && isset($this->_columns[$columnName])) {
            $aOptions = $this->creditScore->toOptionArray();
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
}
