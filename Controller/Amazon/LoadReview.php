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

namespace Payone\Core\Controller\Amazon;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Payone\Core\Model\Exception\AuthorizationException;
use Payone\Core\Model\PayoneConfig;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

/**
 * TransactionStatus receiver
 */
class LoadReview extends \Magento\Framework\App\Action\Action
{
    /**
     * Result factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\ViewInterface
     */
    protected $view;

    /**
     * @var \Payone\Core\Block\Onepage\Review
     */
    protected $reviewBlock;

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
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Coupon factory
     *
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * Object of getconfiguration request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration
     */
    protected $getConfiguration;

    /**
     * Object of getorderreferencedetails request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails
     */
    protected $getOrderReferenceDetails;

    /**
     * Object of setorderreferencedetails request
     *
     * @var \Payone\Core\Model\Api\Request\Genericpayment\SetOrderReferenceDetails
     */
    protected $setOrderReferenceDetails;

    /**
     * Amazon Pay payment object
     *
     * @var \Payone\Core\Model\Methods\AmazonPay
     */
    protected $payment;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Order
     */
    protected $orderHelper;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * Cart management interface
     *
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * Existing Payone error codes mapped to their Amazon error codes
     *
     * @var array
     */
    protected $amazonErrors = [
        109 => 'AmazonRejected',
        900 => 'UnspecifiedError',
        980 => 'TransactionTimedOut',
        981 => 'InvalidPaymentMethod',
        982 => 'AmazonRejected',
        983 => 'ProcessingFailure',
        984 => 'BuyerEqualsSeller',
        985 => 'PaymentMethodNotAllowed',
        986 => 'PaymentPlanNotSet',
        987 => 'ShippingAddressNotSet'
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context                                  $context
     * @param \Magento\Framework\Controller\Result\JsonFactory                       $resultJsonFactory
     * @param \Payone\Core\Block\Onepage\Review                                      $reviewBlock
     * @param \Magento\Framework\App\ViewInterface                                   $view
     * @param \Magento\Framework\View\Result\PageFactory                             $pageFactory
     * @param \Magento\Checkout\Model\Session                                        $checkoutSession
     * @param \Magento\Checkout\Model\Cart                                           $cart
     * @param \Magento\Quote\Api\CartRepositoryInterface                             $quoteRepository
     * @param \Magento\SalesRule\Model\CouponFactory                                 $couponFactory
     * @param \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration         $getConfiguration,
     * @param \Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails $getOrderReferenceDetails,
     * @param \Payone\Core\Model\Api\Request\Genericpayment\SetOrderReferenceDetails $setOrderReferenceDetails
     * @param \Payone\Core\Model\Methods\AmazonPay                                   $payment
     * @param \Payone\Core\Helper\Order                                              $orderHelper
     * @param \Payone\Core\Helper\Checkout                                           $checkoutHelper
     * @param \Magento\Quote\Api\CartManagementInterface                             $cartManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Payone\Core\Block\Onepage\Review $reviewBlock,
        \Magento\Framework\App\ViewInterface $view,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Payone\Core\Model\Api\Request\Genericpayment\GetConfiguration $getConfiguration,
        \Payone\Core\Model\Api\Request\Genericpayment\GetOrderReferenceDetails $getOrderReferenceDetails,
        \Payone\Core\Model\Api\Request\Genericpayment\SetOrderReferenceDetails $setOrderReferenceDetails,
        \Payone\Core\Model\Methods\AmazonPay $payment,
        \Payone\Core\Helper\Order $orderHelper,
        \Payone\Core\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Api\CartManagementInterface $cartManagement
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->view = $view;
        $this->reviewBlock = $reviewBlock;
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->couponFactory = $couponFactory;
        $this->getConfiguration = $getConfiguration;
        $this->getOrderReferenceDetails = $getOrderReferenceDetails;
        $this->setOrderReferenceDetails = $setOrderReferenceDetails;
        $this->payment = $payment;
        $this->orderHelper = $orderHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->cartManagement = $cartManagement;
    }

    /**
     * Executing TransactionStatus handling
     *
     * @return Json|ResponseInterface
     */
    public function execute()
    {
        $aReturnData = [];

        $blSuccess = false;

        $sAction = $this->getRequest()->getParam('action');
        switch ($sAction) {
            case 'confirmSelection':
                $aReturnData = $this->confirmSelection($aReturnData);
                if (!empty($aReturnData['workorderId'])) {
                    $blSuccess = true;
                }
                break;
            case 'placeOrder':
                $aReturnData = $this->placeOrder($aReturnData);
                if (!empty($aReturnData['successUrl'])) {
                    $blSuccess = true;
                }
                break;
            case 'updateShipping':
                $blSuccess = $this->updateShippingMethod($this->getRequest()->getParam('shippingMethod'));
                break;
            case 'updateCoupon':
                $blSuccess = $this->handleCouponRequest();
                break;
            case 'cancelToBasket':
                $this->messageManager->addErrorMessage(__('Sorry, your transaction with Amazon Pay was not successful. Please choose another payment method.'));
                return $this->_redirect('checkout/cart');
        }

        if ($sAction != 'placeOrder' || empty($aReturnData['successUrl'])) {
            $oPageReturn = $this->pageFactory->create(false, ['template' => 'Payone_Core::blank.phtml']);

            $aReturnData['html'] = $oPageReturn->getLayout()->getOutput();
        }

        $aReturnData['success'] = $blSuccess;

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($aReturnData);
    }

    /**
     * Get Amazon workorder_id from session or request
     *
     * @return string
     */
    protected function collectWorkorderId()
    {
        $sWorkorderId = $this->checkoutSession->getAmazonWorkorderId();
        if (empty($sWorkorderId)) {
            $aResult = $this->getConfiguration->sendRequest($this->payment);
            if (isset($aResult['status']) && $aResult['status'] == 'OK' && isset($aResult['workorderid'])) {
                $sWorkorderId = $aResult['workorderid'];
                $this->checkoutSession->setAmazonWorkorderId($aResult['workorderid']);
            }
        }
        return $sWorkorderId;
    }

    /**
     * Update shipping method
     *
     * @param  string $sShippingMethod
     * @return bool
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
        return true;
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

    /**
     * Handle coupon management
     *
     * @return bool
     */
    protected function handleCouponRequest()
    {
        $couponCode = '';
        if ($this->getRequest()->getParam('remove') != 1) {
            $couponCode = trim($this->getRequest()->getParam('couponCode'));
        }

        $cartQuote = $this->checkoutSession->getQuote();
        $oldCouponCode = $cartQuote->getCouponCode();

        $codeLength = strlen($couponCode);
        if (!$codeLength && !strlen($oldCouponCode)) {
            return true;
        }

        try {
            $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;

            $itemsCount = $cartQuote->getItemsCount();
            if ($itemsCount) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                $this->quoteRepository->save($cartQuote);
            }

            if ($codeLength) {
                #$escaper = $this->_objectManager->get('Magento\Framework\Escaper');
                if (!$itemsCount) {
                    if ($isCodeLengthValid) {
                        $coupon = $this->couponFactory->create();
                        $coupon->load($couponCode, 'code');
                        if ($coupon->getId()) {
                            $this->checkoutSession->getQuote()->setCouponCode($couponCode)->save();
                            //$this->messageManager->addSuccess(__('You used coupon code "%1".', $escaper->escapeHtml($couponCode)));
                        } else {
                            //$this->messageManager->addError(__('The coupon code "%1" is not valid.', $escaper->escapeHtml($couponCode)));
                        }
                    } else {
                        //$this->messageManager->addError(__('The coupon code "%1" is not valid.', $escaper->escapeHtml($couponCode)));
                    }
                } else {
                    if ($isCodeLengthValid && $couponCode == $cartQuote->getCouponCode()) {
                        //$this->messageManager->addSuccess(__('You used coupon code "%1".', $escaper->escapeHtml($couponCode)));
                    } else {
                        //$this->messageManager->addError(__('The coupon code "%1" is not valid.', $escaper->escapeHtml($couponCode)));
                        $this->cart->save();
                    }
                }
            } else {
                //$this->messageManager->addSuccess(__('You canceled the coupon code.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            //$this->messageManager->addError(__('We cannot apply the coupon code.'));
            //$this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
        return true;
    }

    /**
     * confirmSelection action
     * Executes getorderreference details
     *
     * @param  array $aReturnData
     * @return array
     */
    protected function confirmSelection($aReturnData)
    {
        $sWorkorderId = $this->collectWorkorderId();
        if (!empty($sWorkorderId)) {
            $amazonReferenceId = $this->getRequest()->getParam('amazonReferenceId');
            $amazonAddressToken = $this->getRequest()->getParam('amazonAddressToken');

            $aResult = $this->getOrderReferenceDetails->sendRequest($this->payment, $sWorkorderId, $amazonReferenceId, $amazonAddressToken);
            if (isset($aResult['status']) && $aResult['status'] == 'OK') {
                $this->checkoutSession->setAmazonAddressToken($amazonAddressToken);
                $this->checkoutSession->setAmazonReferenceId($amazonReferenceId);

                $oQuote = $this->checkoutSession->getQuote();
                $oQuote = $this->orderHelper->updateAddresses($oQuote, $aResult);

                $oPayment = $oQuote->getPayment();
                $oPayment->setMethod(PayoneConfig::METHOD_AMAZONPAY);

                $oQuote->collectTotals()->save();
            }
        }
        $aReturnData['workorderId'] = $sWorkorderId;

        return $aReturnData;
    }

    /**
     * Return error identifier for given error code
     *
     * @param  int $iErrorCode
     * @return string
     */
    protected function getErrorIdentifier($iErrorCode)
    {
        $sIdentifier = 'UnknownError';
        if (isset($this->amazonErrors[$iErrorCode])) {
            $sIdentifier = $this->amazonErrors[$iErrorCode];
        }
        return $sIdentifier;
    }

    /**
     * placeOrder action
     * Generates the order
     *
     * @param  array $aReturnData
     * @return array
     */
    protected function placeOrder($aReturnData)
    {
        $oQuote = $this->checkoutSession->getQuote();
        
        $sWorkorderId = $this->checkoutSession->getAmazonWorkorderId();
        $amazonReferenceId = $this->checkoutSession->getAmazonReferenceId();
        $amazonAddressToken = $this->checkoutSession->getAmazonAddressToken();
        $blSetOrderReferenceDetailsExecuted = $this->checkoutSession->getOrderReferenceDetailsExecuted();

        if (!$blSetOrderReferenceDetailsExecuted) {
            $aResult = $this->setOrderReferenceDetails->sendRequest($this->payment, $oQuote->getGrandTotal(), $sWorkorderId, $amazonReferenceId, $amazonAddressToken);
            if (!isset($aResult['status']) || $aResult['status'] != 'OK') {
                return $aReturnData;
            }
        }

        $this->checkoutSession->setOrderReferenceDetailsExecuted(true);
        try {
            if ($this->checkoutHelper->getCurrentCheckoutMethod($oQuote) == Onepage::METHOD_GUEST) {
                $oQuote->setCustomerId(null)
                    ->setCustomerEmail($oQuote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
            }

            #$oQuote->setPayment($oPayment);
            $oQuote->setInventoryProcessed(false);
            $oQuote->getBillingAddress()->setShouldIgnoreValidation(true);
            $oQuote->getShippingAddress()->setShouldIgnoreValidation(true);
            $oQuote->collectTotals()->save();
            $this->cartManagement->placeOrder($oQuote->getId());
            $oQuote->setIsActive(false)->save();

            $this->unsetSessionVariables();

            $aReturnData['successUrl'] = $this->_url->getUrl('checkout/onepage/success/');
        } catch (AuthorizationException $e) {
            $aResponse = $e->getResponse();
            $aReturnData['errorMessage'] = $this->getErrorIdentifier($aResponse['errorcode']);
            if (isset($aResponse['status']) && $aResponse['status'] == 'ERROR' && in_array($aResponse['errorcode'], [980, 982])) {
                $aReturnData['errorUrl'] = $this->_url->getUrl('payone/amazon/loadReview', ['action' => 'cancelToBasket']);
                $this->unsetSessionVariables();
            }
        } catch (\Exception $e) {
            //error_log($e->getMessage());
            $aReturnData['errorMessage'] = __('There has been an error processing your request.');
        }
        return $aReturnData;
    }

    /**
     * Removes the amazon session variables
     *
     * @return void
     */
    protected function unsetSessionVariables()
    {
        $this->checkoutSession->unsAmazonWorkorderId();
        $this->checkoutSession->unsAmazonReferenceId();
        $this->checkoutSession->unsAmazonAddressToken();
        $this->checkoutSession->unsOrderReferenceDetailsExecuted();
    }
}
