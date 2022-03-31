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

use Magento\Quote\Api\Data\AddressInterface;

/**
 * Class for the PAYONE Server API request "consumerscore"
 */
class Consumerscore extends AddressRequest
{
    /**
     * Send request "addresscheck" to PAYONE server API
     *
     * @param  AddressInterface $oAddress
     * @param  string           $sMode
     * @param  string           $sConsumerscoreType
     * @param  string           $sAddresscheckType
     * @param  string|null      $sSimpleProtectVersion
     * @param  string|null      $sBusinessRelation
     * @param  string|null      $sGender
     * @param  string|null      $sBirthday
     * @return array|bool
     */
    public function sendRequest(AddressInterface $oAddress, $sMode, $sConsumerscoreType, $sAddresscheckType, $sSimpleProtectVersion = null, $sBusinessRelation = null, $sBirthday = null, $sGender = null)
    {
        $this->addParameter('request', 'consumerscore');
        $this->addParameter('mode', $sMode); //Operationmode live or test
        $this->addParameter('aid', $this->shopHelper->getConfigParam('aid')); //ID of PayOne Sub-Account
        $this->addParameter('addresschecktype', $sAddresscheckType);
        $this->addParameter('consumerscoretype', $sConsumerscoreType);
        if ($sBusinessRelation !== null) {
            $this->addParameter('businessrelation', $sBusinessRelation);
        }
        if ($sBirthday !== null) {
            $this->addParameter('birthday', date('Ymd', strtotime($sBirthday)));
        }
        if ($sGender !== null) {
            $this->addParameter('gender', $sGender);
        }
        $this->addParameter('language', $this->shopHelper->getLocale());
        $this->addAddress($oAddress);

        if ($sSimpleProtectVersion !== null) {
            $this->addParameter('sdk_type', 'simple-protect');
            $this->addParameter('sdk_version', $sSimpleProtectVersion);
        }

        return $this->send();
    }
}
