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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Event class to extend the creditmemo object with payone properties
 */
class CreditmemoRegisterBefore implements ObserverInterface
{
    /**
     * Add payone properties to the creditmemo object
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $aInput = $observer->getEvent()->getInput();
        if (isset($aInput['payone_iban']) && isset($aInput['payone_bic'])) {
            $oCreditmemo = $observer->getEvent()->getCreditmemo();
            $oCreditmemo->setPayoneIban($aInput['payone_iban']);
            $oCreditmemo->setPayoneBic($aInput['payone_bic']);
        }
    }
}
