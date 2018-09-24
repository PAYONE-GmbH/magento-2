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

namespace Payone\Core\Model\Methods\OnlineBankTransfer;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for iDeal Card payment method
 */
class Ideal extends OnlineBankTransferBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_OBT_IDEAL;

    /**
     * Payment method sub type
     *
     * @var string
     */
    protected $sSubType = self::METHOD_OBT_SUBTYPE_IDEAL;

    /**
     * Array of all available bank groups
     *
     * @var array
     */
    protected static $aBankGroups = [
        'ABN_AMRO_BANK' => 'ABN Amro',
        'BUNQ_BANK' => 'Bunq',
        'RABOBANK' => 'Rabobank',
        'ASN_BANK' => 'ASN Bank',
        'SNS_BANK' => 'SNS Bank',
        'TRIODOS_BANK' => 'Triodos Bank',
        'SNS_REGIO_BANK' => 'SNS Regio Bank',
        'ING_BANK' => 'ING Bank',
        'KNAB_BANK' => 'Knab Bank',
        'VAN_LANSCHOT_BANKIERS' => 'van Lanschot',
        'MONEYOU' => 'Moneyou',
    ];

    /**
     * Return parameters specific to this payment sub type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getSubTypeSpecificParameters(Order $oOrder)
    {
        $oInfoInstance = $this->getInfoInstance();

        $aParams = [
            'bankcountry' => 'NL',
            'bankgrouptype' => $oInfoInstance->getAdditionalInformation('bank_group'),
        ];

        return $aParams;
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
        $oInfoInstance->setAdditionalInformation('bank_group', $this->toolkitHelper->getAdditionalDataEntry($data, 'bank_group'));

        return $this;
    }

    /**
     * Return available bank groups
     *
     * @return array
     */
    public static function getBankGroups()
    {
        $aReturn = [];

        foreach (self::$aBankGroups as $sKey => $sTitle) {
            $aReturn[] = [
                'id' => $sKey,
                'title' => utf8_encode($sTitle),
            ];
        }

        return $aReturn;
    }
}
