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
use Magento\Framework\Controller\Result\Redirect;

/**
 * Controller for handling the return from PayPal Express
 */
class Returned extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE PayPal return handler
     *
     * @var \Payone\Core\Model\Paypal\ReturnHandler
     */
    protected $returnHandler;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context   $context
     * @param \Magento\Checkout\Model\Session         $checkoutSession
     * @param \Payone\Core\Model\Paypal\ReturnHandler $returnHandler
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Paypal\ReturnHandler $returnHandler
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->returnHandler = $returnHandler;
    }

    /**
     * Redirect to payment-provider or to success page
     *
     * @return Redirect
     */
    public function execute()
    {
        $this->checkoutSession->setIsPayonePayPalExpress(true);
        $sWorkorderId = $this->checkoutSession->getPayoneWorkorderId();
        if ($sWorkorderId) {
            try {
                $this->returnHandler->handlePayPalReturn($sWorkorderId);

                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('payone/onepage/review');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('An error occured during the PayPal Express transaction.'));
            }
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/cart');
    }
}
