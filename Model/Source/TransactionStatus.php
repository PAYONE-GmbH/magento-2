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

namespace Payone\Core\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Payone\Core\Model\PayoneConfig;

/**
 * Source class for existing possible transaction status
 */
class TransactionStatus implements ArrayInterface
{
    /**
     * Return existing possible transaction status
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_APPOINTED,
                'label' => __('APPOINTED'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_CAPTURE,
                'label' => __('CAPTURE'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_PAID,
                'label' => __('PAID'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_UNDERPAID,
                'label' => __('UNDERPAID'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_CANCELATION,
                'label' => __('CANCELATION'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_REFUND,
                'label' => __('REFUND'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_DEBIT,
                'label' => __('DEBIT'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_REMINDER,
                'label' => __('REMINDER'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_VAUTHORIZATION,
                'label' => __('VAUTHORIZATION'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_VSETTLEMENT,
                'label' => __('VSETTLEMENT'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_TRANSFER,
                'label' => __('TRANSFER'),
            ],
            [
                'value' => PayoneConfig::TRANSACTIONSTATUS_INVOICE,
                'label' => __('INVOICE'),
            ],
        ];
    }
}
