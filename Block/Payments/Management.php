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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Payments;

use Magento\Framework\View\Element\Template;
use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\ResourceModel\SavedPaymentData;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey;

/**
 * Block class for saved payment data
 */
class Management extends \Magento\Framework\View\Element\Template
{
    /**
     * SavedPaymentData resource class
     *
     * @var SavedPaymentData
     */
    protected $savedPaymentData;

    /**
     * Customer session object
     *
     * @var Session
     */
    protected $customerSession;

    /**
     * Form key object
     *
     * @var FormKey
     */
    protected $formKey;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param SavedPaymentData $savedPaymentData
     * @param Session          $customerSession
     * @param FormKey          $formKey
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        SavedPaymentData $savedPaymentData,
        Session $customerSession,
        FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->savedPaymentData = $savedPaymentData;
        $this->customerSession = $customerSession;
        $this->formKey = $formKey;
    }

    public function getCardtypeUrl($aData)
    {
        return 'https://cdn.pay1.de/cc/'.strtolower($aData['payment_data']['cardtype']).'/s/default.png';
    }

    /**
     * Load all saved payment data of the current customer
     *
     * @return array
     */
    public function getSavedPaymentData()
    {
        $iCustomerId = $this->customerSession->getCustomerId();
        return $this->savedPaymentData->getSavedPaymentData($iCustomerId, PayoneConfig::METHOD_CREDITCARD, false);
    }

    /**
     * Generate action url
     *
     * @param  int $iId
     * @param  string $sAction
     * @return string
     */
    public function getActionUrl($iId, $sAction)
    {
        return $this->getUrl('payone/payments/'.$sAction, ['id' => $iId, 'form_key' => $this->formKey->getFormKey()]);
    }
}
