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
 * PHP version 8
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2026 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for creditcard payment method
 */
class CreditcardV2 extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_CREDITCARDV2;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\Creditcard';

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'cc';

    /**
     * Wallettype for PAYONE requests
     *
     * @var string|bool
     */
    protected $sWallettype = 'CTP';

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'pseudocardpan',
        'cardholder',
        'cardtype',
        'cardinputmode',
    ];

    /**
     * @return string
     */
    protected function getCardtype()
    {
        $sCardtype = $this->getInfoInstance()->getAdditionalInformation('cardtype');

        // Just using first letter of cardtype might not be correct for all cards, but as long as we don't have a translation table we're rolling with it
        $sCardtype = strtoupper(substr($sCardtype, 0, 1));

        return $sCardtype;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aReturn = [];

        if ($this->getInfoInstance()->getAdditionalInformation('cardinputmode') == "manual") {
            $aReturn['pseudocardpan'] = $this->getInfoInstance()->getAdditionalInformation('pseudocardpan');
            $aReturn['cardholder'] = $this->getInfoInstance()->getAdditionalInformation('cardholder');
        } elseif(in_array($this->getInfoInstance()->getAdditionalInformation('cardinputmode'), ['clickToPay', 'register'])) {
            $aReturn['clearingtype'] = 'wlt';
            $aReturn['wallettype'] = $this->getWallettype();
            $aReturn['cardtype'] = $this->getCardtype();
            $aReturn['add_paydata[paymentcheckout_data]'] = $this->getInfoInstance()->getAdditionalInformation('pseudocardpan');
        }

        return $aReturn;
    }

    /**
     * @return array
     */
    public function getFrontendConfig()
    {
        return [
            'initiatorIdVisa' => $this->getCustomConfigParam('initiator_id_visa'),
            'initiatorIdMastercard' => $this->getCustomConfigParam('initiator_id_mastercard'),
            'dpaId' => $this->getCustomConfigParam('dpa_id'),
            'mode' => $this->getOperationMode(),
            'ctpEnabled' => $this->getCustomConfigParam('clicktopay_enabled'),
            'ctpRegisterEnabled' => $this->getCustomConfigParam('clicktopay_register_enabled'),
        ];
    }

    /**
     * Add the checkout-form-data to the checkout session
     *
     * @param  DataObject $data
     * @return $this
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $oInfoInstance = $this->getInfoInstance();
        foreach ($this->aAssignKeys as $sKey) {
            $sData = $this->toolkitHelper->getAdditionalDataEntry($data, $sKey);
            if ($sData) {
                $oInfoInstance->setAdditionalInformation($sKey, $sData);
            }
        }

        return $this;
    }
}
