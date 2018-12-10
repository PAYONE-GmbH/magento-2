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

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Action\Action;

/**
 * Cancel controller for back links from redirect payment-types
 */
class Cancel extends Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * Url builder object
     *
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory     $orderFactory
     * @param \Magento\Framework\Url                $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url $urlBuilder
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Checkout is canceled and old basket is reactivated
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $this->checkoutSession->setIsPayoneRedirectCancellation(true);
            $this->checkoutSession->unsPayoneWorkorderId();
            $this->checkoutSession->unsIsPayonePayPalExpress();

            $sPaymentMethod = $this->checkoutSession->getPayoneRedirectedPaymentMethod();
            if ($sPaymentMethod) {
                $this->checkoutSession->setPayoneCanceledPaymentMethod($sPaymentMethod);
            }

            if ($this->getRequest()->getParam('error')) {
                $this->checkoutSession->setPayoneIsError(true);
            }

            $orderId = $this->checkoutSession->getLastOrderId();
            $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
            if ($order) {
                $order->cancel()->save();
                $this->checkoutSession->restoreQuote();
                $this->checkoutSession
                    ->unsLastQuoteId()
                    ->unsLastSuccessQuoteId()
                    ->unsLastOrderId();
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Error while canceling the payment'));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setUrl($this->urlBuilder->getUrl('checkout').'#payment');
    }
}
