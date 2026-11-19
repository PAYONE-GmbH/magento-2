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
     * Name of the POST parameter containing the signature for the decouple controller
     *
     * @var string
     */
    const DECOUPLE_SIGNATURE_PARAM = 'payone_decouple_signature';

    /**
     * PAYONE config helper
     *
     * @var \Payone\Core\Helper\Config
     */
    protected $configHelper;

    /**
     * Factory for Magento 2 Curl library
     * A new curl object is created for every request, so that options don't affect other requests
     *
     * @var \Magento\Framework\HTTP\Client\CurlFactory
     */
    protected $curlFactory;

    /**
     * Logger object
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * PAYONE toolkit helper
     *
     * @var \Payone\Core\Helper\Toolkit
     */
    protected $toolkitHelper;

    /**
     * Constructor.
     *
     * @param \Payone\Core\Helper\Config            $configHelper
     * @param \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
     * @param \Psr\Log\LoggerInterface              $logger
     * @param \Payone\Core\Helper\Toolkit           $toolkitHelper
     */
    public function __construct(
        \Payone\Core\Helper\Config $configHelper,
        \Magento\Framework\HTTP\Client\CurlFactory $curlFactory,
        \Psr\Log\LoggerInterface $logger,
        \Payone\Core\Helper\Toolkit $toolkitHelper
    ) {
        $this->configHelper = $configHelper;
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
        $this->toolkitHelper = $toolkitHelper;
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

        $oCurl = $this->curlFactory->create();
        $oCurl->setTimeout($iTimeout);
        $this->configHelper->setCurlSslOptions($oCurl);
        try {
            $oCurl->post($sUrl, $aPostArray);
            $this->log($sUrl.' Response: '.$oCurl->getBody(), $aPostArray);
        } catch(\Exception $exc) {
            $this->log($sUrl.' Exception: '.$exc->getMessage(), $aPostArray);
        }
    }

    /**
     * Generate the signature for a status sent to the decouple controller of the shop
     *
     * @param  array $aPostArray
     * @return string
     */
    protected function getDecoupleSignature($aPostArray)
    {
        return $this->toolkitHelper->getSignature(http_build_query($aPostArray));
    }

    /**
     * Check if the signature of a status received by the decouple controller is valid
     *
     * @param  array  $aPostArray status without the signature parameter
     * @param  string $sSignature
     * @return bool
     */
    public function isDecoupleSignatureValid($aPostArray, $sSignature)
    {
        return $this->toolkitHelper->isSignatureValid(http_build_query($aPostArray), $sSignature);
    }

    /**
     * Forward a request to the decouple controller of the shop and dont wait for a response
     *
     * @param  array  $aPostArray
     * @param  string $sUrl
     * @return void
     */
    public function forwardAsyncRequest($aPostArray, $sUrl)
    {
        if (empty($this->configHelper->getForwardingUrls())) {
            return; // the decouple controller only handles the forwardings, so it doesn't have to be called without them
        }

        // The decouple controller only accepts requests signed by the shop itself
        $aPostArray[self::DECOUPLE_SIGNATURE_PARAM] = $this->getDecoupleSignature($aPostArray);

        $oCurl = $this->curlFactory->create();
	// Increased timeout to 5500ms
	// See payone-gmbh/magento-2 issue #316
        $oCurl->setOption(CURLOPT_TIMEOUT_MS, 5500);
        // Unlike all other external connections, the certificate is not verified here regardless of the ssl_verify setting:
        // This request goes to the shop itself. Shops with a self-signed or internally resolved certificate would otherwise
        // lose all status forwardings without any notice, because errors of this async request can not be logged
        // (it always ends with a timeout exception). The content is protected by the signature added above.
        $oCurl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $oCurl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
	$oCurl->setOption(CURLOPT_FOLLOWLOCATION, true);
        try {
            $oCurl->post($sUrl, $aPostArray);
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
