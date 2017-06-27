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

/**
 * Base class for all online bank transfer payment methods
 */
class OnlineBankTransferBase extends \Payone\Core\Model\Methods\PayoneMethod
{
    /* Payment method sub types */
    const METHOD_OBT_SUBTYPE_SOFORTUEBERWEISUNG = 'PNT';
    const METHOD_OBT_SUBTYPE_GIROPAY = 'GPY';
    const METHOD_OBT_SUBTYPE_EPS = 'EPS';
    const METHOD_OBT_SUBTYPE_POSTFINANCE_EFINANCE = 'PFF';
    const METHOD_OBT_SUBTYPE_POSTFINANCE_CARD = 'PFC';
    const METHOD_OBT_SUBTYPE_IDEAL = 'IDL';
    const METHOD_OBT_SUBTYPE_PRZELEWY = 'P24';

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Payone\Core\Block\Info\Debit';

    /**
     * Clearingtype for PAYONE authorization request
     *
     * @var string
     */
    protected $sClearingtype = 'sb';

    /**
     * Payment method group identifier
     *
     * @var string
     */
    protected $sGroupName = PayoneConfig::METHOD_GROUP_ONLINE_BANK_TRANSFER;

    /**
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentSpecificParameters(Order $oOrder)
    {
        $aBaseParams = [
            'onlinebanktransfertype' => $this->getSubType(),
        ];
        $aSubTypeParams = $this->getSubTypeSpecificParameters($oOrder);
        $aParams = array_merge($aBaseParams, $aSubTypeParams);
        return $aParams;
    }
}
