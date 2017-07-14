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

namespace Payone\Core\Controller\Adminhtml\Information;

/**
 * Controller class for admin information page
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * Return if the user has the needed rights to view this page
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Payone_Core::payone_information');
    }

    /**
     * Displays the information page
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_isAllowed()) {
            $this->_view->loadLayout();
            $this->_setActiveMenu('Payone_Core::payone_information');
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Payone information'));
            $this->_addContent(
                $this->_view->getLayout()->createBlock('Payone\Core\Block\Adminhtml\Information')
            );
            $this->_view->renderLayout();
        }
    }
}
