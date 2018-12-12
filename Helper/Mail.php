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

namespace Payone\Core\Helper;

/**
 * Helper class for sending emails
 */
class Mail extends \Payone\Core\Helper\Base
{
    /**
     * Email transport builder object
     *
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context             $context
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Payone\Core\Helper\Shop                          $shopHelper
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Payone\Core\Helper\Shop $shopHelper,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        parent::__construct($context, $storeManager, $shopHelper);
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Generate array with sender data
     *
     * @return array
     */
    protected function getShopSenderArray()
    {
        return [
            'name' => $this->getConfigParam('name', 'ident_general', 'trans_email'),
            'email' => $this->getConfigParam('email', 'ident_general', 'trans_email'),
        ];
    }

    /**
     * Send email to given recipient
     *
     * @param  string $sRecipient
     * @param  string $sTemplateIdentifier
     * @param  array  $aData
     * @param  bool   $sArea
     * @return void
     */
    public function sendEmail($sRecipient, $sTemplateIdentifier, $aData = [], $sArea = false)
    {
        if ($sArea === false) {
            $sArea = \Magento\Framework\App\Area::AREA_FRONTEND;
        }

        $aOptions = [
            'area' => $sArea,
            'store' => $this->shopHelper->getStoreId(),
        ];

        $oTransport = $this->transportBuilder
            ->setTemplateIdentifier($sTemplateIdentifier)
            ->setTemplateOptions($aOptions)
            ->setTemplateVars($aData)
            ->setFrom($this->getShopSenderArray())
            ->addTo($sRecipient)
            ->getTransport();

        $oTransport->sendMessage();
    }
}
