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

namespace Payone\Core\Model\UiComponent;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;

/**
 * DataProvider extension model
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * PAYONE database helper
     *
     * @var \Payone\Core\Helper\Database
     */
    protected $databaseHelper;

    /**
     * Constructor
     *
     * @param string                       $name
     * @param string                       $primaryFieldName
     * @param string                       $requestFieldName
     * @param Reporting                    $reporting
     * @param SearchCriteriaBuilder        $searchCritBuilder
     * @param RequestInterface             $request
     * @param FilterBuilder                $filterBuilder
     * @param \Payone\Core\Helper\Database $databaseHelper
     * @param array                        $meta
     * @param array                        $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCritBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Payone\Core\Helper\Database $databaseHelper,
        array $meta = [],
        array $data = []
    ) {
        $this->databaseHelper = $databaseHelper; // needs to be in front of constructor, doesnt work otherwise for no apparent reason
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCritBuilder, $request, $filterBuilder, $meta, $data);
    }

    /**
     * This is used to fix the admin grid filter
     * The admin-user can filter by increment_id
     * But the ajax-implementation used for the filter somehow sometimes
     * sends the order-id instead of the increment_id, so this
     * translates the order id to the increment_id
     *
     * @return void
     */
    protected function prepareUpdateUrl()
    {
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' == $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }
            if ($paramValue) {
                $sIncrementId = $this->databaseHelper->getIncrementIdByOrderId($paramValue);
                if ($sIncrementId) {
                    $paramValue = $sIncrementId;
                }
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
                $this->addFilter(
                    $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('eq')->create()
                );
            }
        }
    }
}
