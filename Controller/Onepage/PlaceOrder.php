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

use Payone\Core\Model\PayoneConfig;

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
     * Payone checkout helper
     *
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * Payone order helper
     *
     * @var \Payone\Core\Helper\Order
     */
    protected $orderHelper;

    /**
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Quote\Api\CartManagementInterface         $cartManagement
     * @param \Payone\Core\Helper\Checkout                       $checkoutHelper
     * @param \Payone\Core\Helper\Order                          $orderHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Payone\Core\Helper\Checkout $checkoutHelper,
        \Payone\Core\Helper\Order $orderHelper
    ) {
        $this->agreementsValidator = $agreementValidator;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->checkoutHelper = $checkoutHelper;
        $this->orderHelper = $orderHelper;
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
            $oQuote = $this->checkoutSession->getQuote();

            if ($this->checkoutHelper->getQuoteComparisonString($oQuote) != $this->checkoutSession->getPayoneQuoteComparisonString()) {
                // The basket was changed - abort current checkout
                $this->messageManager->addErrorMessage('An error occured during the Checkout.');
                $this->_redirect('checkout/cart');
                return;
            }

            if (!empty($this->checkoutSession->getPayoneQuoteAddressHash()) && $this->checkoutHelper->getQuoteAddressHash($oQuote) != $this->checkoutSession->getPayoneQuoteAddressHash()) {
                // Address has been changed which is not allowed to happen - reset it to the address given by the Express payment method
                $aExpressAddressResponse = $this->checkoutSession->getPayoneExpressAddressResponse();

                $blUseBilling = true;
                if (in_array($oQuote->getPayment()->getMethod(), [PayoneConfig::METHOD_PAYPAL, PayoneConfig::METHOD_PAYPALV2])) {
                    $blUseBilling = false;
                }
                $oQuote = $this->orderHelper->updateAddresses($oQuote, $aExpressAddressResponse, $blUseBilling);
            }

            $this->placeOrder($oQuote);

            $sPayoneRedirectUrl = $this->checkoutSession->getPayoneRedirectUrl();
            if (!empty($sPayoneRedirectUrl)) {
                $this->checkoutSession->setPayoneCustomerIsRedirected(true);
                $this->checkoutSession->setPayonePayPalExpressRetry(true);
                $this->_redirect($sPayoneRedirectUrl);
                return;
            }

            // "last successful quote"
            $sQuoteId = $oQuote->getId();
            $this->checkoutSession->setLastQuoteId($sQuoteId)->setLastSuccessQuoteId($sQuoteId)->unsPayoneWorkorderId()->unsIsPayonePayPalExpress()->unsPayoneUserAgent()->unsPayoneDeviceFingerprint();

            $oQuote->setIsActive(false)->save();

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
     * @param Magento\Quote\Model\Quote $oQuote
     * @return void
     */
    protected function placeOrder($oQuote)
    {
        $oQuote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$oQuote->getIsVirtual()) {
            $oQuote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        $this->checkoutSession->setPayoneUserAgent($this->getRequest()->getHeader('user-agent'));
        $this->checkoutSession->setPayoneExpressType($oQuote->getPayment()->getMethod());
        if ($oQuote->getPayment()->getMethod() == PayoneConfig::METHOD_PAYPAL) {
            $this->checkoutSession->setIsPayonePayPalExpress(true);
        }
        if ($oQuote->getPayment()->getMethod() == PayoneConfig::METHOD_AMAZONPAYV2) {
            $this->checkoutSession->setIsPayoneAmazonPayAuth(true);
        }
        if ($this->getRequest()->getParam('fingerprint')) {
            $this->checkoutSession->setPayoneDeviceFingerprint($this->getRequest()->getParam('fingerprint'));
        }

        $this->cartManagement->placeOrder($oQuote->getId());
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
