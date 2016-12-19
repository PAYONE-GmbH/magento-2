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
 * Block class for Klarna store-id grid-element
 */
class KlarnaStoreId extends \Payone\Core\Block\Adminhtml\Config\Form\Field\FieldArray\Multiselect
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
     * @var \Payone\Core\Model\Source\KlarnaCountry
     */
    protected $klarnaCountries;

    /**
     * Constructor
     *
     * @param  \Magento\Backend\Block\Template\Context      $context
     * @param  \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param  \Payone\Core\Model\Source\KlarnaCountry      $klarnaCountries
     * @param  array                                        $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Payone\Core\Model\Source\KlarnaCountry $klarnaCountries,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory = $elementFactory;
        $this->klarnaCountries = $klarnaCountries;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('store_id', ['label' => __('Store-ID')]);
        $this->addColumn('countries', ['label' => __('Countries')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Store-ID');
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
        if ($sColumnName == 'countries' && isset($this->_columns[$sColumnName])) {
            $aOptions = $this->klarnaCountries->toOptionArray();
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
