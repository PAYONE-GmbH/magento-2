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

namespace Payone\Core\Helper;

use Magento\Sales\Model\Order;

/**
 * Helper class for Amasty giftcards
 */
class AmastyGiftcard extends \Payone\Core\Helper\Base
{
    /**
     * Array of amasty giftcards
     *
     * @var array
     */
    protected $aAmastyGiftcard = null;

    /**
     * Checks if Amasty Giftcard class is existing and returns the used giftcards for this order
     *
     * We are aware that the ObjectManager should not be called like this,
     * but since most shops won't have this module installed we can't load it with the dependency injection in the constructor.
     *
     * If there is a better way to solve this optional injection/soft dependancy feel free to tell us.
     *
     * @param  string $sQuoteId
     * @param  Order $oOrder
     * @return array
     */
    public function getAmastyGiftCards($sQuoteId, $oOrder)
    {
        if ($this->aAmastyGiftcard === null) {
            $this->aAmastyGiftcard = [];
            if (class_exists('\Amasty\GiftCard\Model\ResourceModel\Quote\Collection')) { // old Amasty module version
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $giftCardsCollection = $objectManager->create('Amasty\GiftCard\Model\ResourceModel\Quote\CollectionFactory');
                $this->aAmastyGiftcard = $giftCardsCollection->create()->getGiftCardsWithAccount($sQuoteId)->getData();
            }

            if ($oOrder && $oOrder->getExtensionAttributes() && is_callable([$oOrder->getExtensionAttributes(), 'getAmGiftcardOrder']) && $oOrder->getExtensionAttributes()->getAmGiftcardOrder()) { // new Amasty module version
                $giftCards = $oOrder->getExtensionAttributes()->getAmGiftcardOrder()->getGiftCards();
                foreach ($giftCards as $giftCard) {
                    // copy fields to fit old format
                    $giftCard['gift_amount'] = $giftCard['amount'];
                    $giftCard['base_gift_amount'] = $giftCard['b_amount'];
                    $this->aAmastyGiftcard[] = $giftCard;
                }
            }
        }
        return $this->aAmastyGiftcard;
    }

    /**
     * Determine if order has used amasty giftcards
     *
     * @param  string $sQuoteId
     * @param  Order $oOrder
     * @return bool
     */
    public function hasAmastyGiftcards($sQuoteId, $oOrder)
    {
        $aGiftCards = $this->getAmastyGiftCards($sQuoteId, $oOrder);
        if (!empty($aGiftCards)) {
            return true;
        }
        return false;
    }
}
