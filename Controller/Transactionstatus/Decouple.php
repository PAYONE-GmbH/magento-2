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

namespace Payone\Core\Controller\Transactionstatus;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Payone\Core\Model\TransactionStatus\Forwarding;
use Magento\Framework\Controller\Result\RawFactory;

/**
 * TransactionStatus decoupler
 */
class Decouple extends \Payone\Core\Controller\ExternalAction
{
    /**
     * PAYONE TransactionStatus Forwarding
     *
     * @var Forwarding
     */
    protected $statusForwarding;

    /**
     * Result factory for file-download
     *
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * Constructor
     *
     * @param Context    $context
     * @param FormKey    $formKey
     * @param Forwarding $statusForwarding
     * @param RawFactory $resultRawFactory
     */
    public function __construct(Context $context, FormKey $formKey, Forwarding $statusForwarding, RawFactory $resultRawFactory) {
        parent::__construct($context, $formKey);

        $this->statusForwarding = $statusForwarding;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Executing TransactionStatus handling
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $aStatus = $this->getRequest()->getPostValue();

        $this->statusForwarding->handleForwardings($aStatus);

        $oResultRaw = $this->resultRawFactory->create();
        $oResultRaw->setContents('');

        return $oResultRaw;
    }
}
