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

namespace Payone\Core\Controller\Payments;

use Magento\Framework\App\Action\Context;
use Payone\Core\Model\ResourceModel\SavedPaymentData;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey\Validator;

/**
 * Controller for setting the default of saved payment data
 */
class SetDefault extends \Magento\Framework\App\Action\Action
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
     * Form key validator
     *
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * Constructor
     *
     * @param Context          $context
     * @param SavedPaymentData $savedPaymentData
     * @param Session          $customerSession
     * @param Validator        $formKeyValidator
     */
    public function __construct(
        Context $context,
        SavedPaymentData $savedPaymentData,
        Session $customerSession,
        Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->savedPaymentData = $savedPaymentData;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $iDataId = $this->getRequest()->getParam('id');
        $iCustomerId = $this->customerSession->getCustomerId();
        if ($iCustomerId) {
            if ($this->formKeyValidator->validate($this->getRequest())) {
                $this->savedPaymentData->setDefault($iDataId, $iCustomerId);
            } else {
                $this->messageManager->addErrorMessage(__('Invalid Form Key. Please refresh the page.'));
            }
        }
        return $this->resultRedirectFactory->create()->setPath('payone/payments/management');
    }
}
