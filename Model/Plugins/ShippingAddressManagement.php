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

namespace Payone\Core\Model\Plugins;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\ShippingAddressManagement as ShippingAddressManagementOrig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Plugin for Magentos ShippingAddressManagement class
 */
class ShippingAddressManagement
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * PAYONE addresscheck request model
     *
     * @var \Payone\Core\Model\Risk\Addresscheck
     */
    protected $addresscheck;

    /**
     * Constructor
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Model\Session            $checkoutSession
     * @param \Payone\Core\Model\Risk\Addresscheck       $addresscheck
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\Risk\Addresscheck $addresscheck
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->addresscheck = $addresscheck;
    }

    /**
     * Execute addresscheck and return the response
     *
     * @param  AddressInterface $oAddress
     * @return array
     */
    protected function handleAddresscheck(AddressInterface $oAddress)
    {
        $aResponse = $this->addresscheck->getResponse($oAddress);
        if (is_array($aResponse)) {
            $sErrorMessage = false;
            if ($aResponse['status'] == 'INVALID') {
                $sErrorMessage = $this->addresscheck->getInvalidMessage($aResponse['customermessage']);
            } elseif ($aResponse['status'] == 'ERROR') {
                if ($this->toolkitHelper->getConfigParam('handle_response_error', 'address_check', 'payone_protect') == 'stop_checkout') {
                    $sErrorMessage = $this->toolkitHelper->getConfigParam('stop_checkout_message', 'address_check', 'payone_protect');
                    if (empty($sErrorMessage)) {
                        $sErrorMessage = 'An error occured during the addresscheck.';
                    }
                }
            }

            if (!empty($sErrorMessage)) {
                throw new LocalizedException(__($sErrorMessage));
            }
        }
        return $aResponse;
    }

    /**
     *
     * @param  ShippingAddressManagementOrig $oSource
     * @param  int                           $sCartId
     * @param  AddressInterface              $oAddress
     * @return array
     */
    public function beforeAssign(ShippingAddressManagementOrig $oSource, $sCartId, AddressInterface $oAddress)
    {
        $sScore = $this->checkoutSession->getPayoneShippingAddresscheckScore();
        if (!$sScore && empty($oAddress->getPayoneAddresscheckScore())) {
            $oQuote = $this->quoteRepository->getActive($sCartId);
            if ($this->addresscheck->isCheckNeededForQuote(false, $oQuote->isVirtual(), $oQuote->getSubtotal())) {
                $aResponse = $this->handleAddresscheck($oAddress);
                if (isset($aResponse['status']) && $aResponse['status'] == 'VALID') {
                    $oAddress = $this->addresscheck->correctAddress($oAddress);
                }
                $sScore = $this->addresscheck->getScore($oAddress);
            }
        }
        if ($sScore) {
            $oAddress->setPayoneAddresscheckScore($sScore);
            $this->checkoutSession->unsPayoneShippingAddresscheckScore();
        }
        return [$sCartId, $oAddress];
    }
}
