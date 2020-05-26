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
     * Payment method code for pseudo-payment-type Klarna
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_KLARNA_BASE;

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
     * Determines if the redirect-parameters have to be added
     * to the authorization-request
     *
     * @var bool
     */
    protected $blNeedsRedirectUrls = true;

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
            'add_paydata[authorization_token]' => $this->getInfoInstance()->getAdditionalInformation('authorization_token'),
        ];

        $oShipping = $oOrder->getShippingAddress();
        if ($oShipping) {
            $aBaseParams['add_paydata[shipping_email]'] = $oOrder->getCustomerEmail();
            $aBaseParams['add_paydata[shipping_title]'] = '';
            $aBaseParams['add_paydata[shipping_telephonenumber]'] = '';
        }

        $aSubTypeParams = $this->getSubTypeSpecificParameters($oOrder);
        $aParams = array_merge($aBaseParams, $aSubTypeParams);
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
        $oInfoInstance->setAdditionalInformation('authorization_token', $this->toolkitHelper->getAdditionalDataEntry($data, 'authorization_token'));

        return $this;
    }
}
