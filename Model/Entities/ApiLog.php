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

namespace Payone\Core\Model\Entities;

use Magento\Framework\Model\AbstractModel;

/**
 * ApiLog entity model
 */
class ApiLog extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Payone\Core\Model\ResourceModel\ApiLog');
    }

    /**
     * Unserialize a given column from the entity
     *
     * @param  string $sKey
     * @param  bool   $blSort
     * @return array
     */
    protected function getUnserializedArray($sKey, $blSort = false)
    {
        $aReturn = [];
        $sRequest = $this->getData($sKey);
        if ($sRequest) {
            $aRequest = unserialize($sRequest);
            if (is_array($aRequest)) {
                $aReturn = $aRequest;
            }
        }
        if ($blSort) {
            ksort($aReturn);
        }
        return $aReturn;
    }

    /**
     * Unserialize raw_request column from entity
     *
     * @return array
     */
    public function getRawRequestArray()
    {
        return $this->getUnserializedArray('raw_request', true);
    }

    /**
     * Unserialize raw_response column from entity
     *
     * @return array
     */
    public function getRawResponseArray()
    {
        return $this->getUnserializedArray('raw_response', true);
    }
}
