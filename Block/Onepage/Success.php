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
 * Block class for success-page extension which displays the mandate-link
 */
class Success extends Template
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Is mandate link to be shown?
     *
     * @var bool|null
     */
    protected $blShowMandateLink = null;

    /**
     * Instruction notes
     *
     * @var string|bool
     */
    protected $sInstructionNotes = false;

    /**
     * Constructor
     *
     * @param  \Magento\Checkout\Model\Session                  $checkoutSession
     * @param  \Magento\Framework\View\Element\Template\Context $context
     * @param  \Payone\Core\Helper\Payment                      $paymentHelper
     * @param  array $data
     * @return void
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        \Payone\Core\Helper\Payment $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Determine if mandate-link has to be shown or not
     *
     * @return bool
     */
    public function showMandateLink()
    {
        if ($this->blShowMandateLink === null) {
            $this->blShowMandateLink = false;
            if ($this->paymentHelper->isMandateManagementDownloadActive()) {
                $order = $this->checkoutSession->getLastRealOrder();
                if ($order->getPayoneMandateId()) {
                    $this->blShowMandateLink = true;
                }
            }
        }
        return $this->blShowMandateLink;
    }

    /**
     * Return instruction notes
     *
     * @return string
     */
    public function getInstructionNotes()
    {
        if ($this->sInstructionNotes === false) {
            $this->sInstructionNotes = $this->checkoutSession->getPayoneInstructionNotes();
            $this->checkoutSession->unsPayoneInstructionNotes();
        }
        return $this->sInstructionNotes;
    }

    /**
     * Determine if extra info has to be shown on success page
     *
     * @return bool
     */
    protected function pageRenderNeeded()
    {
        if ($this->showMandateLink() || $this->getInstructionNotes()) {
            return true;
        }
        return false;
    }

    /**
     * Return block-content if it has to be shown or empty string if not
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->pageRenderNeeded() === true) {
            return parent::toHtml();
        }
        return '';
    }

    /**
     * Return URL to the mandate-download-page
     *
     * @return string
     */
    public function getMandateDownloadUrl()
    {
        return $this->getUrl('payone/mandate/download');
    }
}
