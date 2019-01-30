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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Model\Handler;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\LocalizedException;
use \Magento\Quote\Api\CartRepositoryInterface as QuoteRepo;

class TransactionStatus
{
    /**
     * TransactionStatus array
     *
     * @var array
     */
    protected $status = [];

    /**
     * PAYONE TransactionStatus Mapping
     *
     * @var \Payone\Core\Model\TransactionStatus\Mapping
     */
    protected $statusMapping;

    /**
     * PAYONE TransactionStatus Forwarding
     *
     * @var \Payone\Core\Model\TransactionStatus\Forwarding
     */
    protected $statusForwarding;

    /**
     * Magento event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Constructor
     *
     * @param \Payone\Core\Model\TransactionStatus\Mapping $statusMapping
     * @param \Payone\Core\Model\TransactionStatus\Forwarding $statusForwarding
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Payone\Core\Model\TransactionStatus\Mapping $statusMapping,
        \Payone\Core\Model\TransactionStatus\Forwarding $statusForwarding,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->statusMapping = $statusMapping;
        $this->statusForwarding = $statusForwarding;
        $this->eventManager = $eventManager;
    }

    /**
     * Set status array
     *
     * @param  array $aStatus
     * @return void
     */
    protected function setStatus($aStatus)
    {
        if (is_array($aStatus)) {
            $this->status = $aStatus;
        }
    }

    /**
     * Return status array
     *
     * @return array
     */
    protected function getStatus()
    {
        return $this->status;
    }

    /**
     * Return certain key from status array
     *
     * @param  string $sKey
     * @return string|null
     */
    protected function getParam($sKey)
    {
        if (isset($this->status[$sKey])) {
            return $this->status[$sKey];
        }
        return null;
    }

    /**
     * Handle TransactionStatus
     *
     * @param  Order $oOrder
     * @param  array $aStatus
     * @return void
     */
    public function handle(Order $oOrder, $aStatus)
    {
        $this->setStatus($aStatus);

        $sAction = $this->getParam('txaction');

        if ($oOrder) {
            $oOrder->setPayoneTransactionStatus($sAction);
            $oOrder->save();

            $this->statusMapping->handleMapping($oOrder, $sAction);
        }
        $this->statusForwarding->handleForwardings($this->getStatus());

        $aParams = [
            'order' => $oOrder,
            'transactionstatus' => $this->getStatus(),
        ];

        $this->eventManager->dispatch('payone_core_transactionstatus_all', $aParams);
        $this->eventManager->dispatch('payone_core_transactionstatus_'.$sAction, $aParams);
    }
}