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

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Helper class for everything that has to do with the consumerscore request
 */
class Consumerscore extends \Payone\Core\Helper\Base
{
    const CONFIG_KEY_CONSUMERSCORE_SAMPLE_COUNTER = 'payone_consumerscore_sample_counter';

    /**
     * Config writer resource
     *
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;

    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context                 $context
     * @param \Magento\Store\Model\StoreManagerInterface            $storeManager
     * @param \Payone\Core\Helper\Shop                              $shopHelper
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Payone\Core\Helper\Database                          $databaseHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Payone\Core\Helper\Database $databaseHelper
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->configWriter = $configWriter;
        $this->databaseHelper = $databaseHelper;
    }

    /**
     * Retrieve the creditrating sample counter from config
     *
     * @return int
     */
    public function getConsumerscoreSampleCounter()
    {
        $iCounter = $this->databaseHelper->getConfigParamWithoutCache(
            self::CONFIG_KEY_CONSUMERSCORE_SAMPLE_COUNTER,
            'creditrating',
            'payone_protect'
        );
        if (empty($iCounter) || !is_numeric($iCounter)) {
            $iCounter = 0;
        }
        return $iCounter;
    }

    /**
     * Store new value for creditrating sample counter in config
     *
     * @param  $iCount
     * @return true
     */
    public function setConsumerscoreSampleCounter($iCount)
    {
        $this->configWriter->save(
            'payone_protect/creditrating/'.self::CONFIG_KEY_CONSUMERSCORE_SAMPLE_COUNTER,
            $iCount,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
        return true;
    }

    /**
     * Increment creditrating sample counter in config
     *
     * @return int Returns the new counter value
     */
    public function incrementConsumerscoreSampleCounter()
    {
        $iCounter = $this->getConsumerscoreSampleCounter(); // get current sample counter

        $iCounter++;
        $this->setConsumerscoreSampleCounter($iCounter); // set current sample counter
        return $iCounter;
    }

    /**
     * Determine if a consumerscore sample has to be taken
     *
     * @return bool
     */
    public function isSampleNeeded()
    {
        $iFrequency = $this->getConfigParam('sample_mode_frequency', 'creditrating', 'payone_protect');
        if ((bool)$this->getConfigParam('sample_mode_enabled', 'creditrating', 'payone_protect') && !empty($iFrequency)) {
            $iCounter = $this->getConsumerscoreSampleCounter(); // get current sample counter
            if ($iCounter % $iFrequency !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return if the consumerscore hint text has to be shown on payment selection
     *
     * @return bool
     */
    public function canShowPaymentHintText()
    {
        if ((bool)$this->getConfigParam('enabled', 'creditrating', 'payone_protect')
            && (bool)$this->getConfigParam('payment_hint_enabled', 'creditrating', 'payone_protect')
            && $this->getConfigParam('integration_event', 'creditrating', 'payone_protect') == 'after_payment') {
            return true;
        }
        return false;
    }

    /**
     * Return if the consumerscore agreement message has to be shown on payment selection
     *
     * @return bool
     */
    public function canShowAgreementMessage()
    {
        if ((bool)$this->getConfigParam('enabled', 'creditrating', 'payone_protect')
            && (bool)$this->getConfigParam('agreement_enabled', 'creditrating', 'payone_protect')
            && $this->getConfigParam('integration_event', 'creditrating', 'payone_protect') == 'after_payment') {
            return true;
        }
        return false;
    }

    /**
     * Get worst score
     *
     * @param  array $aScores
     * @return string
     */
    public function getWorstScore($aScores)
    {
        if (array_search('R', $aScores) !== false) { // is there a red score existing?
            return 'R'; // return red as worst score
        }

        if (array_search('Y', $aScores) !== false) { // is there a yellow score existing?
            return 'Y'; // return yellow as worst score
        }
        return 'G'; // return green
    }

    /**
     * Get the allowed methods for the score and transform it into an array
     *
     * @param  string $sScore
     * @return array
     */
    public function getAllowedMethodsForScore($sScore)
    {
        $sMethods = '';
        if ($sScore == 'Y') {
            $sMethods = $this->getConfigParam('allow_payment_methods_yellow', 'creditrating', 'payone_protect');
        } elseif ($sScore == 'R') {
            $sMethods = $this->getConfigParam('allow_payment_methods_red', 'creditrating', 'payone_protect');
        }

        $aMethods = [];
        if (!empty($sScore)) {
            $aMethods = explode(',', $sMethods); // config comes as a concatinated string
        }
        return $aMethods;
    }

    /**
     * Copy the status of old creditrating checks to the new addresses
     * when the lifetime of the old check was still active
     *
     * @param  AddressInterface $oAddress
     * @return void
     */
    public function copyOldStatusToNewAddress(AddressInterface $oAddress)
    {
        $sOldStatus = $this->databaseHelper->getOldAddressStatus($oAddress); // get old score from db
        if (!empty($sOldStatus)) {
            $oAddress->setPayoneProtectScore($sOldStatus)->save(); // add score to quote address object
        }
    }

    /**
     * Determine if the given quote total needs a consumerscore check
     *
     * @param double $dTotal
     * @return bool
     */
    public function isCheckNeededForPrice($dTotal)
    {
        $dMin = $this->getConfigParam('min_order_total', 'creditrating', 'payone_protect');
        $dMax = $this->getConfigParam('max_order_total', 'creditrating', 'payone_protect');
        if (is_numeric($dMin) && is_numeric($dMax) && ($dTotal < $dMin || $dTotal > $dMax)) {
            return false;
        }
        return true;
    }

    /**
     * Base checks if a creditrating check is needed
     *
     * @param string $sIntegrationEvent
     * @param double $dGrandTotal
     * @return bool
     */
    public function isCreditratingNeeded($sIntegrationEvent, $dGrandTotal)
    {
        if ((bool)$this->getConfigParam('enabled', 'creditrating', 'payone_protect') === false) {
            return false;
        }

        if ($this->getConfigParam('integration_event', 'creditrating', 'payone_protect') != $sIntegrationEvent) {
            return false;
        }

        if ($this->isCheckNeededForPrice($dGrandTotal) === false) {
            return false;
        }

        if ($this->isSampleNeeded() === false) {
            return false;
        }
        return true;
    }
}
