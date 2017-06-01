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
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;

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
     * PAYONE debit request class
     *
     * @var \Payone\Core\Model\Api\Request\Managemandate
     */
    protected $managemandateRequest;

    /**
     * Page result factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * Cart management interface
     *
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * Onepage checkout model
     *
     * @var \Magento\Checkout\Model\Type\Onepage
     */
    protected $typeOnepage;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context        $context
     * @param \Magento\Checkout\Model\Session              $checkoutSession
     * @param \Payone\Core\Model\Api\Request\Managemandate $managemandateRequest
     * @param \Magento\Framework\View\Result\PageFactory   $pageFactory
     * @param \Magento\Quote\Api\CartManagementInterface   $cartManagement
     * @param \Magento\Checkout\Model\Type\Onepage         $typeOnepage
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Managemandate $managemandateRequest,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\Type\Onepage $typeOnepage,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->managemandateRequest = $managemandateRequest;
        $this->pageFactory = $pageFactory;
        $this->cartManagement = $cartManagement;
        $this->typeOnepage = $typeOnepage;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Handle debit checkout
     * Display mandate if activated
     * Just create the order if mandate is deactivated
     * Redirect to basket if quote or payment is missing
     *
     * @return null|Page
     */
    public function execute()
    {
        $oPageObject = $this->pageFactory->create();

        $sSelectedShippingMethod = $this->getRequest()->getParam('shipping_method');
        if ($sSelectedShippingMethod) {
            $this->updateShippingMethod($sSelectedShippingMethod);
        }

        return $oPageObject;
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
            #if (!$this->_config->getValue('requireBillingAddress') && !$oQuote->getBillingAddress()->getEmail()) {
            #    $oQuote->getBillingAddress()->setSameAsBilling(1);
            #}
        }
    }
}
