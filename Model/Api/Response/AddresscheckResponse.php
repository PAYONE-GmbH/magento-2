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

namespace Payone\Core\Model\Api\Response;

/**
 * Class for the PAYONE Server API response of the "addresscheck" request
 *
 * @method string getCity()
 * @method string getCountry()
 * @method string getFirstname()
 * @method string getLastname()
 * @method string getPersonstatus()
 * @method string getSecstatus()
 * @method string getStatus()
 * @method string getStreet()
 * @method string getStreetname()
 * @method string getStreetnumber()
 * @method string getZip()
 */
class AddresscheckResponse extends \Magento\Framework\DataObject
{

}
