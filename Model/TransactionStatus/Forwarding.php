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

/**
 * Class for handling the TransactionStatus forwarding
 */
class Forwarding
{
    /**
     * PAYONE config helper
     *
     * @var \Payone\Core\Helper\Config
     */
    protected $configHelper;

    /**
     * Magento 2 Curl library
     *
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * Logger object
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \Payone\Core\Helper\Config            $configHelper
     * @param \Magento\Framework\HTTP\Client\Curl   $curl
     * @param \Psr\Log\LoggerInterface              $logger
     */
    public function __construct(
        \Payone\Core\Helper\Config $configHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Log to log-file if configured
     *
     * @param  string $sMessage
     * @param  array $aPostArray
     * @return void
     */
    protected function log($sMessage, $aPostArray)
    {
        if ((bool)$this->configHelper->getConfigParam('log_active', 'forwarding', 'payone_misc') === true) {
            $sIdent = '';
            if (isset($aPostArray['txid'])) {
                $sIdent = $aPostArray['txid'].' - ';
            }
            $this->logger->info($sIdent.$sMessage);
        }
    }

    /**
     * Execute TransactionStatus forwarding with curl
     *
     * @param  array  $aPostArray
     * @param  string $sUrl
     * @param  int    $iTimeout
     * @return void
     */
    public function forwardRequest($aPostArray, $sUrl, $iTimeout)
    {
        if ($iTimeout == 0) {
            $iTimeout = 45;
        }

        $this->log($sUrl.' Forward with timeout of '.$iTimeout.' seconds', $aPostArray);

        $this->curl->setTimeout($iTimeout);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
        try {
            $this->curl->post($sUrl, $aPostArray);
            $this->log($sUrl.' Response: '.$this->curl->getBody(), $aPostArray);
        } catch(\Exception $exc) {
            $this->log($sUrl.' Exception: '.$exc->getMessage(), $aPostArray);
        }
    }

    /**
     * Forward a request and dont wait for a response
     *
     * @param  array  $aPostArray
     * @param  string $sUrl
     * @return void
     */
    public function forwardAsyncRequest($aPostArray, $sUrl)
    {
	// Increased timeout to 5500ms
	// See payone-gmbh/magento-2 issue #316
        $this->curl->setOption(CURLOPT_TIMEOUT_MS, 5500);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
        try {
            $this->curl->post($sUrl, $aPostArray);
        } catch (\Exception $exc) {
            // Async calls will always throw a timeout exception
        }
    }

    /**
     * Handle single TransactionStatus forwarding
     *
     * @param  array  $aPostArray
     * @param  array  $aForwardEntry
     * @param  string $sStatusAction
     * @return void
     */
    protected function handleSingleForwarding($aPostArray, $aForwardEntry, $sStatusAction)
    {
        foreach ($aForwardEntry['txaction'] as $sForwardAction) {
            if ($sForwardAction == $sStatusAction) {
                $this->forwardRequest($aPostArray, $aForwardEntry['url'], (int)$aForwardEntry['timeout']);
            }
        }
    }

    /**
     * Converts post array to a single-line string for output in log
     *
     * @param  array $aPostArray
     * @return string
     */
    protected function getStatusLogLine($aPostArray)
    {
        $sLine = '';
        foreach ($aPostArray as $sKey => $sValue) {
            if (is_array($sValue)) {
                $sValue = '['.$this->getStatusLogLine($sValue).']';
            }
            $sLine .= $sKey.'='.$sValue.';';
        }
        return $sLine;
    }

    /**
     * Handle TransactionStatus forwarding
     *
     * @param  array $aPostArray
     * @return void
     */
    public function handleForwardings($aPostArray)
    {
        $this->log('Handle StatusForwarding: '.$this->getStatusLogLine($aPostArray), $aPostArray);
        if (isset($aPostArray['txaction'])) {
            $aForwarding = $this->configHelper->getForwardingUrls();
            $sStatusAction = $aPostArray['txaction'];
            foreach ($aForwarding as $aForwardEntry) {
                if (isset($aForwardEntry['txaction']) && !empty($aForwardEntry['txaction'])) {
                    $this->handleSingleForwarding($aPostArray, $aForwardEntry, $sStatusAction);
                }
            }
        }
    }
}
