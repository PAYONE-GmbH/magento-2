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

namespace Payone\Core\Block\Adminhtml\Protocol\Api;

use Payone\Core\Model\Entities\ApiLog;

/**
 * Class for API-log grid block
 */
class View extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Requested ApiLog-entry
     *
     * @var \Payone\Core\Model\Entities\ApiLog
     */
    protected $oApiLog = null;

    /**
     * ApiLog factory
     *
     * @var \Payone\Core\Model\Entities\ApiLogFactory
     */
    protected $apiLogFactory;

    /**
     * Constructor
     *
     * @param  \Magento\Backend\Block\Widget\Context     $context
     * @param  \Payone\Core\Model\Entities\ApiLogFactory $apiLogFactory
     * @param  array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Payone\Core\Model\Entities\ApiLogFactory $apiLogFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiLogFactory = $apiLogFactory;
    }

    /**
     * Returns the currently requested ApiLog-object
     *
     * @return ApiLog
     */
    public function getApiLogEntry()
    {
        if ($this->oApiLog === null) {
            $oApiLog = $this->apiLogFactory->create();
            $oApiLog->load($this->getRequest()->getParam('id'));
            $this->oApiLog = $oApiLog;
        }
        return $this->oApiLog;
    }

    /**
     * Adding the Back button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "setLocation('".$this->getUrl('payone/protocol_api/')."')",
                'class' => 'back'
            ]
        );
        parent::_construct();
    }
}
