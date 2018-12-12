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
 * Model for EPS payment method
 */
class Eps extends OnlineBankTransferBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_OBT_EPS;

    /**
     * Payment method sub type
     *
     * @var string
     */
    protected $sSubType = self::METHOD_OBT_SUBTYPE_EPS;

    /**
     * Array of all available bank groups
     *
     * @var array
     */
    protected static $aBankGroups = [
        'ARZ_OAB' => 'Apothekerbank',
        'ARZ_BAF' => 'Ärztebank',
        'EPS_AAB' => 'Austrian Anadi Bank AG',
        'BA_AUS' => 'Bank Austria',
        'ARZ_BCS' => 'Bankhaus Carl Spängler & Co.AG',
        'EPS_SCHEL' => 'Bankhaus Schelhammer & Schattera AG',
        'BAWAG_PSK' => 'BAWAG P.S.K. AG',
        'EPS_BKS' => 'BKS Bank AG',
        'EPS_BKB' => 'Brüll Kallmus Bank AG',
        'EPS_VLB' => 'BTV VIER LÄNDER BANK',
        'EPS_CBGG' => 'Capital Bank Grawe Gruppe AG',
        'EPS_DB' => 'Dolomitenbank',
        'BAWAG_ESY' => 'Easybank AG',
        'SPARDAT_EBS' => 'Erste Bank und Sparkassen',
        'ARZ_HAA' => 'Hypo Alpe-Adria-Bank International AG',
        'ARZ_VLH' => 'Hypo Landesbank Vorarlberg',
        'EPS_NOEGB' => 'HYPO NOE Gruppe Bank AG',
        'EPS_NOELB' => 'HYPO NOE Landesbank AG',
        'HRAC_OOS' => 'HYPO Oberösterreich, Salzburg, Steiermark',
        'ARZ_HTB' => 'Hypo Tirol Bank AG',
        'EPS_HBL' => 'HYPO-BANK BURGENLAND Aktiengesellschaft',
        'ARZ_IMB' => 'Immo-Bank',
        'EPS_MFB' => 'Marchfelder Bank',
        'EPS_OBAG' => 'Oberbank AG',
        'RAC_RAC' => 'Raiffeisen Bankengruppe Österreich',
        'EPS_SCHOELLER' => 'Schoellerbank AG',
        'EPS_SPDBW' => 'Sparda Bank Wien',
        'EPS_SPDBA' => 'SPARDA-BANK AUSTRIA',
        'ARZ_OVB' => 'Volksbank Gruppe',
        'EPS_VKB' => 'Volkskreditbank AG',
        'EPS_VRBB' => 'VR-Bank Braunau',
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
            'bankcountry' => 'AT',
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
