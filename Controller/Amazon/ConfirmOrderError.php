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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Controller\Amazon;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Action\Action;

/**
 * Cancel controller for back links from redirect payment-types
 */
class ConfirmOrderError extends Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

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
     * @param \Magento\Framework\Url                $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Url $urlBuilder
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Checkout is canceled and old basket is reactivated
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $message = "There was a problem with your payment. Your order hasn't been placed, and you haven't been charged.";
        if ($this->getRequest()->getParam('AuthenticationStatus') == 'Abandoned') {
            $this->checkoutSession->setTriggerInvalidPayment(true);
            return $resultRedirect->setUrl($this->urlBuilder->getUrl('payone/onepage/amazon'));
        }

        $this->checkoutSession->setIsPayoneRedirectCancellation(true);
        $this->checkoutSession->unsAmazonWorkorderId();
        $this->checkoutSession->unsAmazonAddressToken();
        $this->checkoutSession->unsAmazonReferenceId();
        $this->checkoutSession->unsOrderReferenceDetailsExecuted();

        $this->messageManager->addErrorMessage(__($message));

        return $resultRedirect->setUrl($this->urlBuilder->getUrl('checkout/cart'));
    }
}
