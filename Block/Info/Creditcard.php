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

namespace Payone\Core\Block\Info;

use Payone\Core\Model\Source\CreditcardTypes;

class Creditcard extends Base
{
    /**
     * Get long version of given creditcard type
     *
     * @param  string $sShortType
     * @return string
     */
    protected function getCreditcardType($sShortType)
    {
        $aTypes = CreditcardTypes::getCreditcardTypes();
        if (array_key_exists($sShortType, $aTypes) !== false) {
            return $aTypes[$sShortType];
        }
        return '';
    }

    /**
     * Prepare credit card related payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];

        $oInfo = $this->getInfo();
        if ($oInfo->getAdditionalInformation('truncatedcardpan')) {
            $data[(string)__('Credit Card Type:')] = $this->getCreditcardType($oInfo->getAdditionalInformation('cardtype'));
            $data[(string)__('Credit Card Number:')] = $oInfo->getAdditionalInformation('truncatedcardpan');
            $data[(string)__('Expiration Date:')] = $oInfo->getAdditionalInformation('cardexpiredate');
        } else {
            $oStatus = $this->getAppointedStatus();
            $data[(string)__('Credit Card Type:')] = $this->getCreditcardType($oStatus->getCardtype());
            $data[(string)__('Credit Card Number:')] = $oStatus->getCardpan();
            $data[(string)__('Expiration Date:')] = $oStatus->getCardexpiredate();
        }

        $sTransId = $oInfo->getLastTransId();
        if ($sTransId == '') {
            $data[(string)__('Payment has not been processed yet.')] = '';
        } else {
            $data[(string)__('Payment reference:')] = $sTransId;
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
