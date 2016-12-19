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

namespace Payone\Core\Block\Onepage;

use Magento\Framework\View\Element\Template;

/**
 * Block class for checkout debit-mandate page
 */
class Debit extends Template
{
    /**
     * Checkout session object
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Error message
     *
     * @var string|bool
     */
    protected $sErrorMessage = false;

    /**
     * Constructor
     *
     * @param  \Magento\Framework\View\Element\Template\Context $context
     * @param  array $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns the mandate text
     *
     * @return string|bool
     */
    public function getMandateText()
    {
        $aMandate = $this->checkoutSession->getPayoneMandate();
        if ($aMandate &&
            isset($aMandate['mandate_status']) &&
            $aMandate['mandate_status'] == 'pending' &&
            isset($aMandate['mandate_text'])
        ) {
            $sText = $aMandate['mandate_text'];
            return urldecode($sText);
        }
        return false;
    }

    /**
     * Returns the mandate id
     *
     * @return string|bool
     */
    public function getMandateId()
    {
        $aMandate = $this->checkoutSession->getPayoneMandate();
        if ($aMandate && isset($aMandate['mandate_identification'])) {
            return $aMandate['mandate_identification'];
        }
        return false;
    }

    /**
     * Returns the basket-url
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout');
    }

    /**
     * Returns error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->sErrorMessage === false) {
            $this->sErrorMessage = $this->checkoutSession->getPayoneDebitError();
            $this->checkoutSession->unsPayoneDebitError();
        }
        return $this->sErrorMessage;
    }
}
