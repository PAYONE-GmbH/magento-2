<?php

namespace Payone\Core\Block\Adminhtml\Config\Form\Field;

class Label extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Payone Base Helper
     *
     * @var \Payone\Core\Helper\Base
     */
    protected $baseHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Payone\Core\Helper\Base                $baseHelper
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Payone\Core\Helper\Base $baseHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->baseHelper = $baseHelper;
    }

    /**
     * Check if inheritance checkbox has to be rendered
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     */
    protected function _isInheritCheckboxRequired(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return false;
    }

    /**
     * Render scope label
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderScopeLabel(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return '';
    }

    /**
     * Determine if label has to be shown
     *
     * @return bool
     */
    protected function showElement()
    {
        if ($this->baseHelper->getConfigParamByPath("customer/address/dob_show") == "req" && $this->baseHelper->getConfigParamByPath("customer/address/gender_show") == "req") {
            return false;
        }
        return true;
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<td class="value">';
        $html .= $this->_getElementHtml($element);
        if ($element->getComment()) {
            $html .= '<span style="color: red;"><strong>' . $element->getComment() . '</strong></span>';
        }
        $html .= '</td>';
        return $html;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->showElement() === false) {
            return '';
        }
        return parent::render($element);
    }
}
