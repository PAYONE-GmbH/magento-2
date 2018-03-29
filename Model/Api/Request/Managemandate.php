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

namespace Payone\Core\Model\Api\Request;

use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Quote\Model\Quote;

/**
 * Class for the PAYONE Server API request "managemandate"
 */
class Managemandate extends AddressRequest
{
    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop                $shopHelper
     * @param \Payone\Core\Helper\Environment         $environmentHelper
     * @param \Payone\Core\Helper\Api                 $apiHelper
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     * @param \Payone\Core\Helper\Customer            $customerHelper
     * @param \Payone\Core\Helper\Database            $databaseHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper,
        \Payone\Core\Helper\Api $apiHelper,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog,
        \Payone\Core\Helper\Customer $customerHelper,
        \Payone\Core\Helper\Database $databaseHelper
    ) {
        parent::__construct($shopHelper, $environmentHelper, $apiHelper, $apiLog, $customerHelper);
        $this->databaseHelper = $databaseHelper;
    }

    /**
     * Send request "managemandate" to PAYONE server API
     *
     * @param  PayoneMethod $oPayment
     * @param  Quote        $oQuote
     * @return array
     */
    public function sendRequest(PayoneMethod $oPayment, Quote $oQuote)
    {
        $oCustomer = $oQuote->getCustomer();

        $this->addParameter('request', 'managemandate'); // Request method
        $this->addParameter('mode', $oPayment->getOperationMode()); // PayOne Portal Operation Mode (live or test)
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); // ID of PayOne Sub-Account
        $this->addParameter('clearingtype', 'elv');

        $this->addParameter('customerid', $oCustomer->getId());
        $sPayOneUserId = $this->databaseHelper->getPayoneUserIdByCustNr($oCustomer->getId());
        if ($sPayOneUserId) {
            $this->addParameter('userid', $sPayOneUserId);
        }

        $oBilling = $oQuote->getBillingAddress();
        $this->addUserDataParameters(
            $oBilling,
            $oPayment,
            $oCustomer->getGender(),
            $oCustomer->getEmail(),
            $oCustomer->getDob()
        );

        $this->addParameter('language', $this->shopHelper->getLocale());

        $oInfoInstance = $oPayment->getInfoInstance();
        $this->addParameter('bankcountry', $oInfoInstance->getAdditionalInformation('bank_country'));
        $this->addParameter('iban', $oInfoInstance->getAdditionalInformation('iban'));
        if ($oInfoInstance->getAdditionalInformation('bic')) {
            $this->addParameter('bic', $oInfoInstance->getAdditionalInformation('bic'));
        }
        $this->addParameter('currency', $this->apiHelper->getCurrencyFromQuote($oQuote));

        $aResponse = $this->send($oPayment);
        if (is_array($aResponse)) {
            $aResponse['mode'] = $oPayment->getOperationMode();
        }

        return $aResponse;
    }
}
