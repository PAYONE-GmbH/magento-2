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
 * Admin-block for Iframe creditcard configuration
 */
class CreditcardTemplate extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Default configuration
     *
     * @var array
     */
    protected $aDefaultConfig = [
        'Number_type' => 'tel',
        'Number_count' => '30',
        'Number_max' => '16',
        'Number_iframe' => 'standard',
        'Number_width' => '202px',
        'Number_height' => '20px',
        'Number_style' => 'standard',
        'Number_css' => '',
        'CVC_type' => 'tel',
        'CVC_count' => '30',
        'CVC_max' => '4',
        'CVC_iframe' => 'standard',
        'CVC_width' => '202px',
        'CVC_height' => '20px',
        'CVC_style' => 'standard',
        'CVC_css' => '',
        'Month_type' => 'select',
        'Month_count' => '3',
        'Month_max' => '2',
        'Month_iframe' => 'custom',
        'Month_width' => '120px',
        'Month_height' => '20px',
        'Month_style' => 'standard',
        'Month_css' => '',
        'Year_type' => 'select',
        'Year_count' => '5',
        'Year_max' => '4',
        'Year_iframe' => 'custom',
        'Year_width' => '120px',
        'Year_height' => '20px',
        'Year_style' => 'standard',
        'Year_css' => '',
        'Iframe_width' => '365px',
        'Iframe_height' => '30px',
        'Standard_input' => "width:223px;height:30px;padding: 0 9px;font-size:14px;font-family:'Helvetica Neue',Verdana,Arial,sans-serif;",
        'Standard_selection' => 'width:100px;',
    ];

    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Payone_Core::system/config/form/field/creditcard_template.phtml';

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
     * Get all field-types
     *
     * @return array
     */
    public function getCCFields()
    {
        return [
            'Number',
            'CVC',
            'Month',
            'Year',
        ];
    }

    /**
     * Get available styles for dropdown
     *
     * @return array
     */
    public function getCCStyles()
    {
        return [
            'standard' => __('Standard'),
            'custom' => __('Custom'),
        ];
    }

    /**
     * Get available types for dropdown
     *
     * @param  string $sField
     * @return array
     */
    public function getCCTypes($sField)
    {
        $aTypes = [];
        if ($sField == 'Month' || $sField == 'Year') {
            $aTypes['select'] = __('Select');
        }
        $aTypes['tel'] = __('Numeric');
        $aTypes['password'] = __('Password');
        $aTypes['text'] = __('Text');
        return $aTypes;
    }

    /**
     * Get configured value or default value
     *
     * @param  string $sIdent
     * @return string
     */
    public function fcpoGetValue($sIdent)
    {
        $sReturn = '';

        $aValues = $this->getElement()->getValue();
        if (is_array($aValues) === false) { // no array given? -> value from config.xml
            $aValues = json_decode($aValues, true); // convert string to array
        }

        if (isset($aValues[$sIdent])) {
            $sReturn = $aValues[$sIdent];
        } elseif (isset($this->aDefaultConfig[$sIdent])) {
            $sReturn = $this->aDefaultConfig[$sIdent];
        }
        $sReturn = str_replace('"', "&quot;", $sReturn);
        return $sReturn;
    }
}
