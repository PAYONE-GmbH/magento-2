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

namespace Payone\Core\Helper;

/**
 * Helper class for everything that has to do with hosted Iframe
 */
class HostedIframe extends \Payone\Core\Helper\Base
{
    /**
     * Configuration params for the hosted Iframe creditcard implementation
     *
     * @var array
     */
    protected $aHostedParams = null;

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Payone\Core\Helper\Payment                $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Payment $paymentHelper
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Get hosted params configuration
     *
     * @return array
     */
    protected function getHostedParams()
    {
        if ($this->aHostedParams === null) {
            $this->aHostedParams = [];
            $sHostedParams = $this->getConfigParam('cc_template', 'creditcard'); // get params from config
            if ($sHostedParams) { // params set in config?
                $aHostedParams = $this->unserialize($sHostedParams); // array from serialized string
                if (is_array($aHostedParams) && !empty($aHostedParams)) {
                    $this->aHostedParams = $aHostedParams;
                }
            }
        }
        return $this->aHostedParams;
    }

    /**
     * Get the field config for one field type for hosted Iframe implementation
     *
     * @param  string $sName
     * @param  string $sParamPrefix
     * @return array
     */
    protected function getFieldConfigField($sName, $sParamPrefix)
    {
        $aHostedParams = $this->getHostedParams();

        $aField = [];
        if (!empty($aHostedParams)) {
            $aField['selector'] = $sName;
            $aField['type'] = $aHostedParams[$sParamPrefix.'_type'];
            $aField['size'] = $aHostedParams[$sParamPrefix.'_count'];
            $aField['maxlength'] = $aHostedParams[$sParamPrefix.'_max'];
            if ($aHostedParams[$sParamPrefix.'_style'] == "custom") {
                $aField['style'] = $aHostedParams[$sParamPrefix.'_css'];
            }
            if ($aHostedParams[$sParamPrefix.'_iframe'] == "custom") {
                $aField['iframe'] = [
                    'width' => $aHostedParams[$sParamPrefix.'_width'],
                    'height' => $aHostedParams[$sParamPrefix.'_height'],
                ];
            }
        }
        return $aField;
    }

    /**
     * Create field config array
     *
     * @return array
     */
    protected function getFieldConfig()
    {
        $aFields = [];
        $aFields['cardpan'] = $this->getFieldConfigField('cardpan', 'Number');
        if ($this->paymentHelper->isCheckCvcActive() === true) { // cvc field activated?
            $aFields['cardcvc2'] = $this->getFieldConfigField('cardcvc2', 'CVC');
        }
        $aFields['cardexpiremonth'] = $this->getFieldConfigField('cardexpiremonth', 'Month');
        $aFields['cardexpireyear'] = $this->getFieldConfigField('cardexpireyear', 'Year');
        return $aFields;
    }

    /**
     * Create default style array
     *
     * @param  array $aHostedParams
     * @return array
     */
    protected function getDefaultStyles($aHostedParams)
    {
        $aDefaultStyle = [];
        $aDefaultStyle['input'] = $aHostedParams['Standard_input'];
        $aDefaultStyle['select'] = $aHostedParams['Standard_selection'];
        $aDefaultStyle['iframe'] = [
            'width' => $aHostedParams['Iframe_width'],
            'height' => $aHostedParams['Iframe_height'],
        ];
        return $aDefaultStyle;
    }

    /**
     * Generate the complete hosted iframe configuration
     *
     * @return array
     */
    public function getHostedFieldConfig()
    {
        $aHostedParams = $this->getHostedParams(); // get hosted params from config

        $aFieldConfig = [];
        if (!empty($aHostedParams)) { // hosted iframe config existing?
            $aFieldConfig['fields'] = $this->getFieldConfig(); // generate config for all field types
            $aFieldConfig['defaultStyle'] = $this->getDefaultStyles($aHostedParams);
            if ($aHostedParams['Errors_active'] == "true") {
                $aFieldConfig['error'] = 'errorOutput'; // area to display error-messages (optional)
                $aFieldConfig['language'] = $aHostedParams['Errors_lang']; // has to be defined in javascript
            }
        }
        return $aFieldConfig;
    }
}
