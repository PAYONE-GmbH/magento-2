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
 * @copyright 2003 - 2022 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods\BNPL;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

class Debit extends BNPLBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_BNPL_DEBIT;

    /**
     * Payment method sub type
     *
     * @var string
     */
    protected $sSubType = self::METHOD_BNPL_SUBTYPE_DEBIT;

    /**
     * Return parameters specific to this payment sub type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getSubTypeSpecificParameters(Order $oOrder)
    {
        $oInfoInstance = $this->getInfoInstance();
        $oBilling = $oOrder->getBillingAddress();

        $aParams = [
            'bankaccountholder' => $oBilling->getFirstname().' '.$oBilling->getLastname(),
            'iban' => $oInfoInstance->getAdditionalInformation('iban'),
        ];

        return $aParams;
    }
}
