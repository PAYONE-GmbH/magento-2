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

namespace Payone\Core\Helper;

use Payone\Core\Model\PayoneConfig;

/**
 * Helper class for everything that has to do with request
 */
class Request extends \Payone\Core\Helper\Base
{
    /**
     * PAYONE environment helper
     *
     * @var \Payone\Core\Helper\Environment
     */
    protected $environmentHelper;

    /**
     * PAYONE shop helper
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Payone\Core\Helper\Environment            $environmentHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\Environment $environmentHelper
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->environmentHelper = $environmentHelper;
        $this->shopHelper = $shopHelper;
    }

    /**
     * Get bankaccountcheck request for javascript in the checkout
     *
     * @return array
     */
    public function getBankaccountCheckRequest()
    {
        if ($this->getConfigParam('check_bankaccount', PayoneConfig::METHOD_DEBIT, 'payone_payment') == '1') {
            $aRequest = [
                'request' => 'bankaccountcheck',
                'responsetype' => 'JSON',
                'mode' => $this->getConfigParam('mode', PayoneConfig::METHOD_DEBIT, 'payone_payment'),
                'mid' => $this->getConfigParam('mid'), // your MID
                'aid' => $this->getConfigParam('aid'), // your AID
                'portalid' => $this->getConfigParam('portalid'), // your PortalId
                'encoding' => $this->environmentHelper->getEncoding(), // desired encoding
                'language' => $this->shopHelper->getLocale(),
                'checktype' => $this->getConfigParam('bankaccountcheck_type', PayoneConfig::METHOD_DEBIT, 'payone_payment'),
                'hash' => $this->getBankaccountCheckRequestHash(),
                'integrator_name' => 'Magento2',
                'integrator_version' => $this->shopHelper->getMagentoVersion(),
                'solution_name' => 'fatchip',
                'solution_version' => PayoneConfig::MODULE_VERSION,
            ];
            return $aRequest;
        }
        return '';
    }

    /**
     * Get hosted iframe request hash
     *
     * @return string
     */
    public function getHostedIframeRequestCCHash()
    {
        $sHash = md5(
            $this->getConfigParam('aid').
            $this->environmentHelper->getEncoding().
            $this->getConfigParam('mid').
            $this->getConfigParam('mode', PayoneConfig::METHOD_CREDITCARD, 'payone_payment').
            $this->getConfigParam('portalid').
            'creditcardcheck'.
            'JSON'.
            'yes'.
            $this->getConfigParam('key')
        );
        return $sHash;
    }

    /**
     * Get bankaccount check request hash
     *
     * @return string
     */
    public function getBankaccountCheckRequestHash()
    {
        $sHash = md5(
            $this->getConfigParam('aid').
            $this->getConfigParam('bankaccountcheck_type', PayoneConfig::METHOD_DEBIT, 'payone_payment').
            $this->environmentHelper->getEncoding().
            $this->getConfigParam('mid').
            $this->getConfigParam('mode', PayoneConfig::METHOD_CREDITCARD, 'payone_payment').
            $this->getConfigParam('portalid').
            'bankaccountcheck'.
            'JSON'.
            $this->getConfigParam('key')
        );
        return $sHash;
    }

    /**
     * Get hosted iframe request for javascript in the checkout
     *
     * @return array
     */
    public function getHostedIframeRequest()
    {
        $aRequest = [
            'request' => 'creditcardcheck',
            'responsetype' => 'JSON', // fixed value
            'mode' => $this->getConfigParam('mode', PayoneConfig::METHOD_CREDITCARD, 'payone_payment'), // desired mode
            'mid' => $this->getConfigParam('mid'), // your MID
            'aid' => $this->getConfigParam('aid'), // your AID
            'portalid' => $this->getConfigParam('portalid'), // your PortalId
            'encoding' => $this->environmentHelper->getEncoding(), // desired encoding
            'storecarddata' => 'yes', // fixed value
            'hash' => $this->getHostedIframeRequestCCHash()
        ];
        return $aRequest;
    }
}
