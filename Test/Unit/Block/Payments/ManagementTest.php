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

namespace Payone\Core\Test\Unit\Block\Payments;

use Payone\Core\Block\Payments\Management as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Model\ResourceModel\SavedPaymentData;

class ManagementTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var SavedPaymentData
     */
    private $savedPaymentData;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);

        $this->customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->customerSession->method('getCustomerId')->willReturn(123);

        $this->savedPaymentData = $this->getMockBuilder(SavedPaymentData::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'customerSession' => $this->customerSession,
            'savedPaymentData' => $this->savedPaymentData
        ]);
    }

    public function testGetSavedPaymentData()
    {
        $expected = ['success' => true];
        $this->savedPaymentData->method('getSavedPaymentData')->willReturn($expected);

        $result = $this->classToTest->getSavedPaymentData();
        $this->assertEquals($expected, $result);
    }

    public function testGetActionUrl()
    {
        $expected = 'expected';

        $this->urlBuilder->method('getUrl')->willReturn($expected);

        $result = $this->classToTest->getActionUrl('1', 'delete');
        $this->assertEquals($expected, $result);
    }

    public function testGetCardtypeUrl()
    {
        $expected = 'https://cdn.pay1.de/cc/v/s/default.png';

        $aData = ['payment_data' => ['cardtype' => 'V']];

        $result = $this->classToTest->getCardtypeUrl($aData);
        $this->assertEquals($expected, $result);
    }
}
