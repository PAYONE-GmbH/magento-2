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

use Magento\Payment\Model\MethodList as OrigMethodList;
use Magento\Payment\Model\MethodInterface;
use Payone\Core\Model\PayoneConfig;
use Magento\Quote\Model\Quote;

/**
 * Plugin for Magentos MethodList class
 */
class MethodList
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Payment ban entity
     *
     * @var \Payone\Core\Model\ResourceModel\PaymentBan
     */
    protected $paymentBan;

    /**
     * PAYONE Simple Protect implementation
     *
     * @var \Payone\Core\Model\SimpleProtect\SimpleProtectInterface
     */
    protected $simpleProtect;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session                         $checkoutSession
     * @param \Payone\Core\Model\ResourceModel\PaymentBan             $paymentBan
     * @param \Payone\Core\Model\SimpleProtect\SimpleProtectInterface $simpleProtect
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\ResourceModel\PaymentBan $paymentBan,
        \Payone\Core\Model\SimpleProtect\SimpleProtectInterface $simpleProtect
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentBan = $paymentBan;
        $this->simpleProtect = $simpleProtect;
    }

    /**
     * Get quote object from session
     *
     * @return Quote
     */
    protected function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Return banned payment methods for the current user
     *
     * @param  Quote $oQuote
     * @return array
     */
    protected function getBannedPaymentMethods(Quote $oQuote)
    {
        $aBans = [];
        if (!empty($oQuote->getCustomerId())) {
            $aBans = $this->paymentBan->getPaymentBans($oQuote->getCustomerId());
        } else { // guest checkout
            $aSessionBans = $this->checkoutSession->getPayonePaymentBans();
            if (!empty($aSessionBans)) {
                $aBans = $aSessionBans;
            }
        }
        return $aBans;
    }

    /**
     * Remove banned paymenttypes
     *
     * @param  array $aPaymentMethods
     * @param  Quote $oQuote
     * @return array
     */
    protected function removeBannedPaymentMethods($aPaymentMethods, Quote $oQuote)
    {
        $aBannedMethos = $this->getBannedPaymentMethods($oQuote);
        if (empty($aBannedMethos)) {
            return $aPaymentMethods;
        }

        for($i = 0; $i < count($aPaymentMethods); $i++) {
            $sCode = $aPaymentMethods[$i]->getCode();
            if (array_key_exists($sCode, $aBannedMethos) !== false) {
                $iBannedUntil = strtotime($aBannedMethos[$sCode]);
                if ($iBannedUntil > time()) {
                    unset($aPaymentMethods[$i]);
                }
            }
        }
        return $aPaymentMethods;
    }

    /**
     * Remove Amazon Pay from payment method array
     *
     * @param  array $aPaymentMethods
     * @return array
     */
    public function removeAmazonPay($aPaymentMethods)
    {
        for($i = 0; $i < count($aPaymentMethods); $i++) {
            if ($aPaymentMethods[$i]->getCode() == PayoneConfig::METHOD_AMAZONPAY) {
                unset($aPaymentMethods[$i]);
            }
        }
        return $aPaymentMethods;
    }

    /**
     * Plugin for methot getAvailableMethods
     *
     * Used to filter out payment methods
     *
     * @param  OrigMethodList    $subject
     * @param  MethodInterface[] $aPaymentMethods
     * @return MethodInterface[]
     */
    public function afterGetAvailableMethods(OrigMethodList $subject, $aPaymentMethods)
    {
        $oQuote = $this->getQuote();

        // Send code execution to simple protect custom implementation
        // Method may filter some payment methods out
        // Throwing an exception to send the customer back to shipping address selection is also possible
        $aPaymentMethods = $this->simpleProtect->handlePrePaymentSelection($oQuote, $aPaymentMethods);

        $aPaymentMethods = $this->removeBannedPaymentMethods($aPaymentMethods, $oQuote);
        $aPaymentMethods = $this->removeAmazonPay($aPaymentMethods);

        return $aPaymentMethods;
    }
}
