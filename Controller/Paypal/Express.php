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

namespace Payone\Core\Controller\Paypal;

use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\Quote;
use Payone\Core\Model\PayoneConfig;

/**
 * Controller for PayPal Express initiation
 */
class Express extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE request model
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress
     */
    protected $genericRequest;

    /**
     * PayPal payment model
     *
     * @var \Payone\Core\Model\Methods\Paypal
     */
    protected $paypalPayment;

    /**
     * Checkout helper
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context                       $context
     * @param \Magento\Checkout\Model\Session                             $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress $genericRequest
     * @param \Payone\Core\Model\Methods\Paypal                           $paypalPayment
     * @param \Magento\Checkout\Helper\Data                               $checkoutHelper
     * @param \Magento\Customer\Model\Session                             $customerSession
     * @param \Payone\Core\Helper\Payment                                 $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Genericpayment\PayPalExpress $genericRequest,
        \Payone\Core\Model\Methods\Paypal $paypalPayment,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Payone\Core\Helper\Payment $paymentHelper
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->genericRequest = $genericRequest;
        $this->paypalPayment = $paypalPayment;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Determine if a logged in customer is required for an express checkout
     * For example needed for virtual products ( download-products etc. )
     *
     * @param  Quote $oQuote
     * @return bool
     */
    protected function loginNeededForExpressCheckout(Quote $oQuote)
    {
        $oCustomer = $this->customerSession->getCustomerDataObject();
        if (!$oCustomer->getId()) {
            $sCheckoutMethod = $oQuote->getCheckoutMethod();
            if ((!$sCheckoutMethod || $sCheckoutMethod != \Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER)
                && !$this->checkoutHelper->isAllowedGuestCheckout($oQuote, $oQuote->getStoreId())
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Redirect to payment-provider or to success page
     *
     * @return void
     */
    public function execute()
    {
        $oQuote = $this->checkoutSession->getQuote();

        if ($this->paymentHelper->isPayPalExpressActive() && $oQuote && $oQuote->hasItems()) {
            if ($this->loginNeededForExpressCheckout($oQuote)) {
                $this->messageManager->addNoticeMessage(__('Please sign in to check out.'));

                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('customer/account/login');
            }

            $this->paypalPayment->setIsPayPalExpress(true);
            $aResponse = $this->genericRequest->sendRequest($oQuote, $this->paypalPayment);
            if ($aResponse['status'] == 'ERROR') {
                $this->messageManager->addError(__($aResponse['customermessage']));

                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/cart');
            } elseif ($aResponse['status'] == 'REDIRECT') {
                $oPayment = $oQuote->getPayment();
                $oPayment->setMethod(PayoneConfig::METHOD_PAYPAL);
                $oQuote->setPayment($oPayment);
                $oQuote->save();

                $this->checkoutSession->setPayoneWorkorderId($aResponse['workorderid']);
                $this->_redirect($aResponse['redirecturl']);
            }
            return;
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/cart');
    }
}
