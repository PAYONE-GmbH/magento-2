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

namespace Payone\Core\Model\Methods;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for Klarna invoice payment method
 */
class Klarna extends PayoneMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_KLARNA;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\SafeInvoice';

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'fnc';

    /**
     * Determines if the invoice information has to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsProductInfo = true;

    /**
     * Keys that need to be assigned to the additionalinformation fields
     *
     * @var array
     */
    protected $aAssignKeys = [
        'telephone',
        'addinfo',
        'del_addinfo',
        'gender',
        'personal_id'
    ];

    /**
     * Map of value that can be directly added to the PAYONE request
     *
     * @var array
     */
    protected $aDirectAssignMap = [
        'telephone' => 'telephonenumber',
        'addinfo' => 'addressaddition',
        'del_addinfo' => 'shipping_addressaddition',
        'personal_id' => 'personalid',
        'dob' => 'birthday',
    ];

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aParams = [
            'financingtype' => 'KLV',
        ];

        $oInfoInstance = $this->getInfoInstance();
        foreach ($this->aDirectAssignMap as $sFrom => $sTo) {
            $sValue = $oInfoInstance->getAdditionalInformation($sFrom);
            if ($sValue) {
                $aParams[$sTo] = $sValue;
            }
        }

        $sGender = $oInfoInstance->getAdditionalInformation('gender');
        if ($sGender) {
            $aParams['gender'] = $sGender;
            $aParams['salutation'] = $sGender == 'm' ? 'Herr' : 'Frau';
        }

        return $aParams;
    }

    /**
     * Returns formatted birthday if possible
     *
     * @param  DataObject $data
     * @return string|false
     */
    protected function getFormattedBirthday(DataObject $data)
    {
        $sFormattedDob = false;

        $sBirthday = $this->toolkitHelper->getAdditionalDataEntry($data, 'birthday');
        $sBirthmonth = $this->toolkitHelper->getAdditionalDataEntry($data, 'birthmonth');
        $sBirthyear = $this->toolkitHelper->getAdditionalDataEntry($data, 'birthyear');
        if ($sBirthday && $sBirthmonth && $sBirthyear) {
            $sDob = $sBirthyear.'-'.$sBirthmonth.'-'.$sBirthday;
            $iDobTime = strtotime($sDob);
            if ($iDobTime !== false) {
                $sFormattedDob = date('Ymd', $iDobTime);
            }
        }
        return $sFormattedDob;
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

        $sFormattedDob = $this->getFormattedBirthday($data);
        if ($sFormattedDob !== false) {
            $oInfoInstance->setAdditionalInformation('dob', $sFormattedDob);
        }

        return $this;
    }
}
