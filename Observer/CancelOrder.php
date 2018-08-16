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

namespace Payone\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Payone\Core\Model\Handler\Cancellation;

/**
 * Event class to prevent the basket from getting lost with redirect payment types
 * when the customer uses the browser back-button
 */
class CancelOrder implements ObserverInterface
{
    /**
     * Checkout session
     *
     * @var Cancellation
     */
    protected $cancellation;

    /**
     * Constructor
     *
     * @param Cancellation $cancellation
     */
    public function __construct(Cancellation $cancellation)
    {
        $this->cancellation = $cancellation;
    }

    /**
     * @param  Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $this->cancellation->handle();
    }
}
