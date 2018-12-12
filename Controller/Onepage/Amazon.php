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

namespace Payone\Core\Controller\Onepage;

use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Payone\Core\Model\PayoneConfig;
use Magento\Quote\Model\Quote;

/**
 * Controller for mandate management with debit payment
 */
class Amazon extends \Magento\Framework\App\Action\Action
{
    /**
     * Page result factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

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
     * Checkout helper
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory   $pageFactory
     * @param \Magento\Checkout\Model\Session              $checkoutSession
     * @param \Magento\Customer\Model\Session              $customerSession
     * @param \Payone\Core\Helper\Payment                  $paymentHelper
     * @param \Magento\Checkout\Helper\Data                $checkoutHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Payone\Core\Helper\Payment $paymentHelper,
        \Magento\Checkout\Helper\Data $checkoutHelper
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * Determine if a logged in customer is required for an express checkout
     * For example needed for virtual products ( download-products etc. )
     *
     * @param  Quote $oQuote
     * @return bool
     */
    protected function isLoginNeeded(Quote $oQuote)
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
     * Display AmazonPay checkout
     *
     * @return Page
     */
    public function execute()
    {
        $oQuote = $this->checkoutSession->getQuote();
        if (!$this->paymentHelper->isPaymentMethodActive(PayoneConfig::METHOD_AMAZONPAY) || !$oQuote || !$oQuote->hasItems()) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout/cart');
        }

        if ($this->isLoginNeeded($oQuote)) {
            $this->messageManager->addNoticeMessage(__('Please sign in to check out.'));

            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('customer/account/login');
        }

        $oPageObject = $this->pageFactory->create();
        return $oPageObject;
    }
}
