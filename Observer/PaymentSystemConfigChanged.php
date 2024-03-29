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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\InfoInterface;
use Payone\Core\Helper\Ratepay;

/**
 * Event class to set the orderstatus to new and pending
 */
class PaymentSystemConfigChanged implements ObserverInterface
{
    /**
     * @var Ratepay
     */
    protected $ratepayHelper;

    /**
     * Constructor
     *
     * @param Ratepay $ratepayHelper
     */
    public function __construct(Ratepay $ratepayHelper)
    {
        $this->ratepayHelper = $ratepayHelper;
    }

    /**
     * Imports Ratepay profile configurations for saved config path
     *
     * @param  string $sChangedPath
     * @return void
     */
    protected function handleRatepayShopConfig($sChangedPath)
    {
        $aShopConfig = $this->ratepayHelper->getRatepayShopConfigByPath($sChangedPath);
        $sPaymentMethod = $this->ratepayHelper->getPaymentMethodFromPath($sChangedPath);
        foreach ($aShopConfig as $aConfig) {
            $this->ratepayHelper->importProfileConfiguration($aConfig['shop_id'], $aConfig['currency'], $sPaymentMethod);
        }
    }

    /**
     * Execute certain tasks after the payone_payment section is being saved in the backend
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $aChangedPaths = $observer->getChangedPaths();
        foreach ($aChangedPaths as $sChangedPath) {
            if (stripos($sChangedPath, "ratepay_shop_config") !== false) {
                $this->handleRatepayShopConfig($sChangedPath);
            }
        }
    }
}
