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

namespace Payone\Core\Block\Adminhtml\Protocol\Transactionstatus;

use Payone\Core\Model\Entities\TransactionStatus;

/**
 * Class for TransactionStatus grid block
 */
class View extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Requested TransactionStatus-entry
     *
     * @var TransactionStatus
     */
    protected $oTransactionStatus = null;

    /**
     * TransactionStatus factory
     *
     * @var \Payone\Core\Model\Entities\TransactionStatusFactory
     */
    protected $statusFactory;

    /**
     *
     * @param \Magento\Backend\Block\Widget\Context                $context
     * @param \Payone\Core\Model\Entities\TransactionStatusFactory $statusFactory
     * @param array                                                $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Payone\Core\Model\Entities\TransactionStatusFactory $statusFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->statusFactory = $statusFactory;
    }

    /**
     * Get current transaction status object
     *
     * @return TransactionStatus
     */
    public function getTransactionStatusEntry()
    {
        if ($this->oTransactionStatus === null) {
            $oTransactionStatus = $this->statusFactory->create();
            $oTransactionStatus->load($this->getRequest()->getParam('id'));
            $this->oTransactionStatus = $oTransactionStatus;
        }
        return $this->oTransactionStatus;
    }

    /**
     * Initialization method
     *
     * @return void
     */
    protected function _construct()
    {
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "setLocation('".$this->getUrl('payone/protocol_transactionstatus/')."')",
                'class' => 'back'
            ]
        );
        parent::_construct();
    }
}
