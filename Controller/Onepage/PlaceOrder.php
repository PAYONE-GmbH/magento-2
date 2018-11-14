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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Controller\Onepage;

/**
 * Controller for creating the PaypalExpress orders
 */
class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Api\AgreementsValidatorInterface
     */
    protected $agreementsValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Quote\Api\CartManagementInterface         $cartManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartManagementInterface $cartManagement
    ) {
        $this->agreementsValidator = $agreementValidator;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        parent::__construct($context);
    }

    /**
     * Submit the order
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if ($this->isValidationRequired() &&
            !$this->agreementsValidator->isValid(array_keys($this->getRequest()->getPost('agreement', [])))
        ) {
            $e = new \Magento\Framework\Exception\LocalizedException(
                __('Please agree to all the terms and conditions before placing the order.')
            );
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_redirect('*/*/review');
            return;
        }

        try {
            $this->placeOrder();

            $this->_redirect('checkout/onepage/success');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t place the order.')
            );
            $this->_redirect('*/*/review');
        }
    }

    /**
     * Place the order and put it in a finished state
     *
     * @return void
     */
    protected function placeOrder()
    {
        $oQuote = $this->checkoutSession->getQuote();

        if ($oQuote->getSubtotal() != $this->checkoutSession->getPayoneGenericpaymentSubtotal()) {
            // The basket was changed - abort current checkout
            $this->messageManager->addErrorMessage('An error occured during the Checkout.');
            $this->_redirect('checkout/cart');
            return;
        }

        $oQuote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$oQuote->getIsVirtual()) {
            $oQuote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        $this->cartManagement->placeOrder($oQuote->getId());

        // "last successful quote"
        $sQuoteId = $oQuote->getId();
        $this->checkoutSession->setLastQuoteId($sQuoteId)->setLastSuccessQuoteId($sQuoteId)->unsPayoneWorkorderId()->unsIsPayonePayPalExpress();

        $oQuote->setIsActive(false)->save();
    }

    /**
     * Return true if agreements validation required
     *
     * @return bool
     */
    protected function isValidationRequired()
    {
        return is_array($this->getRequest()->getBeforeForwardInfo()) && empty($this->getRequest()->getBeforeForwardInfo());
    }
}
