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

namespace Payone\Core\Model\TransactionStatus;

use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Class for handling the TransactionStatus mapping
 */
class Mapping
{
    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * Order status history factory
     *
     * @var OrderStatusHistoryInterfaceFactory
     */
    private $historyFactory;

    /**
     * Order status history repository
     *
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $historyRepository;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Payment  $paymentHelper
     * @param \Payone\Core\Helper\Database $databaseHelper
     * @param OrderStatusHistoryInterfaceFactory $historyFactory
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     */
    public function __construct(
        \Payone\Core\Helper\Payment $paymentHelper,
        \Payone\Core\Helper\Database $databaseHelper,
        OrderStatusHistoryInterfaceFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $historyRepository
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->databaseHelper = $databaseHelper;
        $this->historyFactory = $historyFactory;
        $this->historyRepository = $historyRepository;
    }

    /**
     * Set the order to the state configured by the status mapping
     *
     * @param  Order  $oOrder
     * @param  string $sAction
     * @return void
     */
    public function handleMapping(Order $oOrder, $sAction)
    {
        $sPaymentCode = $oOrder->getPayment()->getMethod();
        $aStatusMapping = $this->paymentHelper->getStatusMappingByCode($sPaymentCode);
        if (isset($aStatusMapping[$sAction])) {
            $sStatus = $aStatusMapping[$sAction];
            $sState = $this->databaseHelper->getStateByStatus($sStatus);

            if ($oOrder->isCanceled() && $sState != Order::STATE_CANCELED) {
                $oHistory = $this->historyFactory->create();
                $oHistory->setParentId($oOrder->getId());
                $oHistory->setStatus($oOrder->getStatus());
                $oHistory->setComment('Received PAYONE status "'.$sAction.'".');
                $oHistory->setEntityName(Order::ENTITY);
                $oHistory->setIsCustomerNotified(0);
                $oHistory->setIsVisibleOnFront(0);
                $this->historyRepository->save($oHistory);
                return;
            }

            $sMsg = 'Received PAYONE status "'.$sAction.'". Order set to "'.$sStatus.'" by PAYONE StatusMapping';
            $oOrder->addStatusToHistory($sStatus, $sMsg, false);

            if ($sState) {
                $oOrder->setState($sState);
            }
            $oOrder->save();
        }
    }
}
