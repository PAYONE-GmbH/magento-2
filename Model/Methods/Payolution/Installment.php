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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Methods\Payolution;

use Payone\Core\Model\PayoneConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 * Model for Payolution installment payment method
 */
class Installment extends PayolutionBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_PAYOLUTION_INSTALLMENT;

    /**
     * Payment method sub type
     *
     * @var string
     */
    protected $sSubType = self::METHOD_PAYOLUTION_SUBTYPE_INSTALLMENT;

    /**
     * Payment method long sub type
     *
     * @var string|bool
     */
    protected $sLongSubType = 'Payolution-Installment';

    /**
     * Returns authorization-mode
     * Barzahlen only supports preauthorization
     *
     * @return string
     */
    public function getAuthorizationMode()
    {
        return PayoneConfig::REQUEST_TYPE_AUTHORIZATION;
    }

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
            'iban' => $oInfoInstance->getAdditionalInformation('iban'),
            'bic' => $oInfoInstance->getAdditionalInformation('bic'),
            'add_paydata[installment_duration]' => $oInfoInstance->getAdditionalInformation('duration'),
            'workorderid' => $this->checkoutSession->getInstallmentWorkorderId()
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
        $oInfoInstance->setAdditionalInformation('iban', $this->toolkitHelper->getAdditionalDataEntry($data, 'iban'));
        $oInfoInstance->setAdditionalInformation('bic', $this->toolkitHelper->getAdditionalDataEntry($data, 'bic'));
        $oInfoInstance->setAdditionalInformation('duration', $this->toolkitHelper->getAdditionalDataEntry($data, 'duration'));

        return $this;
    }
}
