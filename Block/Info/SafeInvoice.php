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

class SafeInvoice extends Base
{
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

        $sTransId = $this->getInfo()->getLastTransId();
        if ($sTransId == '') {
            $data[(string)__('Payment has not been processed yet.')] = '';
        } else {
            $oStatus = $this->getAppointedStatus();
            $data[(string)__('Payment reference')] = $sTransId;
            $data[(string)__('Accountholder')] = $oStatus->getClearingBankaccountholder();
            $data[(string)__('IBAN')] = $oStatus->getClearingBankiban();
            $data[(string)__('BIC')] = $oStatus->getClearingBankbic();
            $data[(string)__('Bank')] = $oStatus->getClearingBankname();
            $data[(string)__('Bank country')] = $oStatus->getClearingBankcountry();
            $data[(string)__('Bank city')] = $oStatus->getClearingBankcity();
            $data[(string)__('Payment Reference')] = $oStatus->getClearingReference();
            $data[(string)__('Legal note')] = $oStatus->getClearingLegalnote();
            $data[(string)__('Instruction note')] = $oStatus->getClearingInstructionnote();
            $data[(string)__('Due date')] = $oStatus->getClearingDuedate();
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
