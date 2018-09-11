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

namespace Payone\Core\Controller\Payments;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Payone\Core\Helper\Base;
use Payone\Core\Model\PayoneConfig;

/**
 * Manage saved payment data controller
 */
class Management extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * PAYONE base helper
     *
     * @var Base
     */
    protected $baseHelper;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Base $baseHelper
     */
    public function __construct(Context $context, PageFactory $pageFactory, Base $baseHelper)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if ((bool)$this->baseHelper->getConfigParam('save_data_enabled', PayoneConfig::METHOD_CREDITCARD, 'payone_payment') === true) {
            $resultPage = $this->pageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('Creditcard management'));

            return $resultPage;
        }
        return $this->resultRedirectFactory->create()->setPath('customer/account');
    }
}
