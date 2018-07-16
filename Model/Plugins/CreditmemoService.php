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

namespace Payone\Core\Model\Plugins;

use Magento\Sales\Model\Service\CreditmemoService as CreditmemoServiceOriginal;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Model\Api\Request\Debit;

class CreditmemoService
{
    /**
     * Checkout session model
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * API-log resource model
     *
     * @var \Payone\Core\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Payone\Core\Model\ResourceModel\ApiLog $apiLog
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Model\ResourceModel\ApiLog $apiLog
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->apiLog = $apiLog;
    }

    /**
     * @param  CreditmemoServiceOriginal $subject
     * @param  callable                  $proceed
     * @param  CreditmemoInterface       $creditmemo
     * @param  bool                      $offlineRequested
     * @return CreditmemoInterface
     * @throws LocalizedException|\Exception
     */
    public function aroundRefund(
        CreditmemoServiceOriginal $subject,
        callable $proceed,
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        try {
            $return = $proceed($creditmemo, $offlineRequested);
        } catch(\Exception $ex) {
            $aRequest = $this->checkoutSession->getPayoneDebitRequest();
            if (is_array($aRequest) && !empty($aRequest)) {
                $aResponse = $this->checkoutSession->getPayoneDebitResponse();
                $sOrderId = $this->checkoutSession->getPayoneDebitOrderId();

                // Rewrite the log-entry after it was rolled back in the db-transaction
                $this->apiLog->addApiLogEntry($aRequest, $aResponse, $aResponse['status'], $sOrderId);
            }
            $this->checkoutSession->unsPayoneDebitRequest();
            $this->checkoutSession->unsPayoneDebitResponse();
            $this->checkoutSession->unsPayoneDebitOrderId();
            throw $ex;
        }
        return $return;
    }
}