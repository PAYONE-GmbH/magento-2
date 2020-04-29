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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods\Klarna;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\Methods\PayoneMethod;
use Magento\Framework\DataObject;

/**
 * Base class for all Payolution methods
 */
class KlarnaBase extends PayoneMethod
{
    /* Payment method sub types */
    const METHOD_KLARNA_SUBTYPE_INVOICE = 'KIV';
    const METHOD_KLARNA_SUBTYPE_DEBIT = 'KDD';
    const METHOD_KLARNA_SUBTYPE_INSTALLMENT = 'KIS';

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'fnc';

    /**
     * Payment method group identifier
     *
     * @var string
     */
    protected $sGroupName = PayoneConfig::METHOD_GROUP_KLARNA;

    /**
     * Payment method long sub type
     *
     * @var string|bool
     */
    protected $sLongSubType = false;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        ''///@TODO
    ];

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\ClearingReference';

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aBaseParams = [
            'financingtype' => $this->getSubType(),
            'api_version' => '3.11',
            //'workorderid' => $this->getInfoInstance()->getAdditionalInformation('workorderid'),
        ];

        $aSubTypeParams = $this->getSubTypeSpecificParameters($oOrder);
        $aParams = array_merge($aBaseParams, $aSubTypeParams);
        return $aParams;
    }

    /**
     * Method to trigger the Payone genericpayment request pre-check
     *
     * @param  float $dAmount
     * @return array
     * @throws LocalizedException
     */
    public function sendPayonePreCheck($dAmount)
    {
        $oQuote = $this->checkoutSession->getQuote();
        if ($this->shopHelper->getConfigParam('currency') == 'display') {
            $dAmount = $oQuote->getGrandTotal(); // send display amount instead of base amount
        }
        $aResponse = $this->precheckRequest->sendRequest($this, $oQuote, $dAmount);

        if ($aResponse['status'] == 'ERROR') {// request returned an error
            throw new LocalizedException(__($aResponse['errorcode'].' - '.$aResponse['customermessage']));
        } elseif ($aResponse['status'] == 'OK') {
            $this->getInfoInstance()->setAdditionalInformation('workorderid', $aResponse['workorderid']);
        }
        return $aResponse;
    }

    /**
     * Return long subtype
     *
     * @return string
     */
    public function getLongSubType()
    {
        return $this->sLongSubType;
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
