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
class Debit extends \Magento\Framework\App\Action\Action
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
     * Constructor
     *
     * @param  \Magento\Framework\App\Action\Context        $context
     * @param  \Magento\Checkout\Model\Session              $checkoutSession
     * @param  \Payone\Core\Model\Api\Request\Managemandate $managemandateRequest
     * @param  \Magento\Framework\View\Result\PageFactory   $pageFactory
     * @param  \Magento\Quote\Api\CartManagementInterface   $cartManagement
     * @param  \Magento\Checkout\Model\Type\Onepage         $typeOnepage
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Api\Request\Managemandate $managemandateRequest,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\Type\Onepage $typeOnepage
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->managemandateRequest = $managemandateRequest;
        $this->pageFactory = $pageFactory;
        $this->cartManagement = $cartManagement;
        $this->typeOnepage = $typeOnepage;
    }

    /**
     * Execute managemandate request and handle response
     *
     * @param  PayoneMethod $oPayment
     * @param  Quote        $oQuote
     * @return array
     * @throws LocalizedException
     */
    protected function handleManagemandateRequest(PayoneMethod $oPayment, Quote $oQuote)
    {
        $aResponse = $this->managemandateRequest->sendRequest($oPayment, $oQuote);
        if ($aResponse['status'] == 'ERROR') {// request was not successfull
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
        } elseif (is_array($aResponse) && array_key_exists('mandate_status', $aResponse) !== false) {
            // write mandate to session
            $this->checkoutSession->setPayoneMandate($aResponse);
        }
        return $aResponse;
    }

    /**
     *
     * @param  PayoneMethod $oPayment
     * @param  Quote $oQuote
     * @return void|Page
     */
    protected function handleMandate(PayoneMethod $oPayment, Quote $oQuote)
    {
        if ($oPayment->getCustomConfigParam('sepa_mandate_enabled')) {
            $aMandate = $this->checkoutSession->getPayoneMandate();
            if (!$aMandate) {// Is initial call, so get the mandate
                $aResponse = $this->handleManagemandateRequest($oPayment, $oQuote);
                if ($aResponse && array_key_exists('mandate_status', $aResponse) !== false && $aResponse['mandate_status'] == 'pending') {
                    $oPageObject = $this->pageFactory->create();
                    return $oPageObject;
                }
            } elseif ($this->getRequest()->getParam('mandate_granted') == 1 &&
                     $this->getRequest()->getParam('mandate_id') != $aMandate['mandate_identification']
            ) {// Is return from the mandate-page with granted mandate but mandate id mismatch
                $this->_redirect($this->_url->getUrl('checkout'));
                return;
            } // else - mandate is granted so proceed to success page
        }
        // trigger order creation
        $this->cartManagement->placeOrder($oQuote->getId());
        $this->_redirect($this->_url->getUrl('checkout/onepage/success'));
    }

    /**
     * Handle debit checkout
     * Display mandate if activated
     * Just create the order if mandate is deactivated
     * Redirect to basket if quote or payment is missing
     *
     * @return void|Page
     */
    public function execute()
    {
        $oQuote = $this->checkoutSession->getQuote();
        $oQuote->setCheckoutMethod($this->typeOnepage->getCheckoutMethod());

        $oPayment = $oQuote->getPayment()->getMethodInstance();
        if (!$oQuote || !$oPayment) {// something is wrong, redirect to checkout start
            $this->_redirect($this->_url->getUrl('checkout'));
            return;
        }

        try {
            return $this->handleMandate($oPayment, $oQuote);
        } catch (\Exception $ex) {
            $this->checkoutSession->setPayoneDebitError($ex->getMessage());
            $oPageObject = $this->pageFactory->create();
            return $oPageObject;
        }
    }
}
