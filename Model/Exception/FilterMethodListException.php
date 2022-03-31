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

namespace Payone\Core\Model\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Class FilterMethodListException
 *
 * This exception can be used to filter out payment methods after the current payment method has been declined
 *
 * @package Payone\Core\Model\Exception
 */
class FilterMethodListException extends LocalizedException
{
    /**
     * @var string[]
     */
    protected $safePaymentMethods;

    /**
     * Constructor
     *
     * By adding a $safePaymentMethods array filled with "safe" payment types you can trigger to have
     * all other payment methods filtered out from the payment method selection page in the checkout
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param string[]|null             $safePaymentMethods
     */
    public function __construct(Phrase $phrase, $safePaymentMethods = null)
    {
        parent::__construct($phrase, null, 0);

        $this->safePaymentMethods = $safePaymentMethods;
    }

    /**
     * Get parameters, these will be returned in the place-order ajax call
     *
     * @return array
     */
    public function getParameters()
    {
        if (is_array($this->safePaymentMethods) && !empty($this->safePaymentMethods)) {
            return ['paymentMethodWhitelist' => $this->safePaymentMethods];
        }
        return [];
    }
}