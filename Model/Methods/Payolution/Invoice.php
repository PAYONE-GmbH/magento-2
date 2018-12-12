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
use Magento\Payment\Model\InfoInterface;

/**
 * Model for Payolution invoice payment method
 */
class Invoice extends PayolutionBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = PayoneConfig::METHOD_PAYOLUTION_INVOICE;

    /**
     * Payment method sub type
     *
     * @var string
     */
    protected $sSubType = self::METHOD_PAYOLUTION_SUBTYPE_INVOICE;

    /**
     * Payment method long sub type
     *
     * @var string|bool
     */
    protected $sLongSubType = 'Payolution-Invoicing';

    /**
     * Authorize payment abstract method
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return $this
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if ($this->shopHelper->getConfigParam('currency') == 'display') {
            $amount = $payment->getOrder()->getTotalDue(); // send display amount instead of base amount
        }
        $this->sendPayonePreCheck($amount);
        return parent::authorize($payment, $amount);
    }
}
