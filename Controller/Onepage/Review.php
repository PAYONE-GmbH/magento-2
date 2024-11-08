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

use Magento\Framework\View\Result\Page;
use Magento\Quote\Model\Quote;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect as CoreRedirect;
use Payone\Core\Model\Methods\AmazonPayV2;
use Payone\Core\Model\PayoneConfig;

/**
 * Controller for mandate management with debit payment
 */
class Review extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Page result factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Payone\Core\Model\Api\Request\Genericpayment\UpdateCheckoutSession
     */
    protected $updateCheckoutSession;

    /**
     * List of all PAYONE payment methods available for this review step
     *
     * @var array
     */
    protected $availableReviewMethods = [
        PayoneConfig::METHOD_PAYPAL,
        PayoneConfig::METHOD_PAYPALV2,
        PayoneConfig::METHOD_PAYDIREKT,
        PayoneConfig::METHOD_AMAZONPAYV2,
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context                               $context
     * @param \Magento\Checkout\Model\Session                                     $checkoutSession
     * @param \Magento\Framework\View\Result\PageFactory                          $pageFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface                          $quoteRepository
     * @param \Payone\Core\Model\Api\Request\Genericpayment\UpdateCheckoutSession $updateCheckoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Payone\Core\Model\Api\Request\Genericpayment\UpdateCheckoutSession $updateCheckoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->pageFactory = $pageFactory;
        $this->quoteRepository = $quoteRepository;
        $this->updateCheckoutSession = $updateCheckoutSession;
    }

    /**
     * Render order review
     * Redirect to basket if quote or payment is missing
     *
     * @return null|Page|CoreRedirect
     */
    public function execute()
    {
        if ($this->canReviewBeShown() === false) {
            /** @var CoreRedirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout');
        }

        $oPageObject = $this->pageFactory->create();

        $sSelectedShippingMethod = $this->getRequest()->getParam('shipping_method');
        if ($sSelectedShippingMethod) {
            $this->updateShippingMethod($sSelectedShippingMethod);
        }

        return $oPageObject;
    }

    /**
     * Validates if the review step can be shown by checking some status flags
     *
     * @return bool
     */
    protected function canReviewBeShown()
    {
        if (!in_array($this->checkoutSession->getQuote()->getPayment()->getMethod(), $this->availableReviewMethods)) {
            return false;
        }

        $sWorkorderId = $this->checkoutSession->getPayoneWorkorderId();
        if (empty($sWorkorderId)) {
            return false;
        }
        return true;
    }

    /**
     * Update shipping method
     *
     * @param  string $sShippingMethod
     * @return void
     */
    protected function updateShippingMethod($sShippingMethod)
    {
        $oQuote = $this->checkoutSession->getQuote();
        $oShippingAddress = $oQuote->getShippingAddress();
        if (!$oQuote->getIsVirtual() && $oShippingAddress) {
            if ($sShippingMethod != $oShippingAddress->getShippingMethod()) {
                $this->ignoreAddressValidation($oQuote);
                $oShippingAddress->setShippingMethod($sShippingMethod)->setCollectShippingRates(true);
                $cartExtension = $oQuote->getExtensionAttributes();
                if ($cartExtension && $cartExtension->getShippingAssignments()) {
                    $cartExtension->getShippingAssignments()[0]->getShipping()->setMethod($sShippingMethod);
                }
                $oQuote->collectTotals();
                $this->quoteRepository->save($oQuote);

                $oPayment = $oQuote->getPayment()->getMethodInstance();
                $sWorkorderId = $this->checkoutSession->getPayoneWorkorderId();
                if ($oPayment instanceof AmazonPayV2) {
                    $this->updateCheckoutSession->sendRequest($oPayment, $oQuote, $sWorkorderId);
                }
            }
        }
    }

    /**
     * Disable validation to make sure addresses will always be saved
     *
     * @param  Quote $oQuote
     * @return void
     */
    protected function ignoreAddressValidation(Quote $oQuote)
    {
        $oQuote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$oQuote->getIsVirtual()) {
            $oQuote->getShippingAddress()->setShouldIgnoreValidation(true);
        }
    }
}
