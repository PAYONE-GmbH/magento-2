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

namespace Payone\Core\Model;

use Payone\Core\Model\Entities\TransactionStatus;
use Payone\Core\Model\Entities\TransactionStatusFactory;
use Payone\Core\Model\ResourceModel\TransactionStatus as ResourceModel;

/**
 * TransactionStatus resource model
 */
class TransactionStatusRepository
{
    /**
     * ResourceModel object
     *
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * Factory for creating the object
     *
     * @var TransactionStatusFactory
     */
    protected $transactionStatusFactory;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel
     * @param TransactionStatusFactory $transactionStatusFactory
     */
    public function __construct(ResourceModel $resourceModel, TransactionStatusFactory $transactionStatusFactory)
    {
        $this->resourceModel = $resourceModel;
        $this->transactionStatusFactory = $transactionStatusFactory;
    }

    /**
     * @param $sTxid
     * @return TransactionStatus
     */
    public function getAppointedByTxid($sTxid)
    {
        $sId = $this->resourceModel->getAppointedIdByTxid($sTxid);

        $oStatus = $this->transactionStatusFactory->create();
        $oStatus->load($sId);

        return $oStatus;
    }
}
