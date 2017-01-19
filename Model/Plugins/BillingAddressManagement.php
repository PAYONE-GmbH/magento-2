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

namespace Payone\Core\Model\Plugins;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\BillingAddressManagement as BillingAddressManagementOrig;

/**
 * Plugin for Magentos BillingAddressManagement class
 */
class BillingAddressManagement
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * PAYONE addresscheck request model
     *
     * @var \Payone\Core\Model\Risk\Addresscheck
     */
    protected $addresscheck;

    /**
     * Constructor
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Payone\Core\Model\Risk\Addresscheck       $addresscheck
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Payone\Core\Model\Risk\Addresscheck $addresscheck
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->addresscheck = $addresscheck;
    }

    /**
     * This writes the addresscheck score to the quote address
     *
     * @param  BillingAddressManagementOrig $oSource
     * @param  int                          $sCartId
     * @param  AddressInterface             $oAddress
     * @param  bool                         $useForShipping
     * @return array
     */
    public function beforeAssign(BillingAddressManagementOrig $oSource, $sCartId, AddressInterface $oAddress, $useForShipping = false)
    {
        $oQuote = $this->quoteRepository->getActive($sCartId);
        $oAddress = $this->addresscheck->handleAddressManagement($oAddress, $oQuote);
        return [$sCartId, $oAddress, $useForShipping];
    }
}
