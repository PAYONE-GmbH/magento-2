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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\HostedIframe;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\Payment;
use Payone\Core\Helper\Shop;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class HostedIframeTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var HostedIframe
     */
    private $hostedIframe;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var Toolkit
     */
    private $toolkitHelper;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('isCheckCvcActive')->willReturn(true);

        $this->toolkitHelper = $this->objectManager->getObject(Toolkit::class, [
            'shopHelper' => $this->objectManager->getObject(Shop::class)
        ]);

        $this->hostedIframe = $this->objectManager->getObject(HostedIframe::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'paymentHelper' => $paymentHelper,
            'toolkitHelper' => $this->toolkitHelper
        ]);
    }

    public function testGetHostedFieldConfigEmpty()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/creditcard/cc_template', ScopeInterface::SCOPE_STORE, null, $this->toolkitHelper->serialize('string')]
                ]
            );
        $result = $this->hostedIframe->getHostedFieldConfig();
        $expected = [];
        $this->assertEquals($expected, $result);
    }

    public function testGetHostedFieldConfig()
    {
        $aHostedConfig = [
            "Number_type" => "tel",
            "Number_count" => "30",
            "Number_max" => "16",
            "Number_iframe" => "standard",
            "Number_style" => "custom",
            "Number_css" => "",
            "CVC_type" => "tel",
            "CVC_count" => "30",
            "CVC_max" => "4",
            "CVC_iframe" => "standard",
            "CVC_style" => "custom",
            "CVC_css" => "",
            "Month_type" => "select",
            "Month_count" => "3",
            "Month_max" => "2",
            "Month_iframe" => "custom",
            "Month_width" => "120px",
            "Month_height" => "20px",
            "Month_style" => "standard",
            "Year_type" => "select",
            "Year_count" => "5",
            "Year_max" => "4",
            "Year_iframe" => "custom",
            "Year_width" => "120px",
            "Year_height" => "20px",
            "Year_style" => "standard",
            "Standard_input" => "",
            "Standard_selection" => "width:100px;",
            "Iframe_width" => "365px",
            "Iframe_height" => "30px",
            "Errors_active" => "true",
            "Errors_lang" => "de"
        ];

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/creditcard/cc_template', ScopeInterface::SCOPE_STORE, null, $this->toolkitHelper->serialize($aHostedConfig)]
                ]
            );
        $result = $this->hostedIframe->getHostedFieldConfig();
        $expected = [
            'fields' => [
                'cardpan' => [
                    'selector' => 'cardpan',
                    'type' => 'tel',
                    'size' => '30',
                    'maxlength' => '16',
                    'style' => ''
                ],
                'cardcvc2' => [
                    'selector' => 'cardcvc2',
                    'type' => 'tel',
                    'size' => '30',
                    'maxlength' => '4',
                    'style' => ''
                ],
                'cardexpiremonth' => [
                    'selector' => 'cardexpiremonth',
                    'type' => 'select',
                    'size' => '3',
                    'maxlength' => '2',
                    'iframe' => [
                        'width' => '120px',
                        'height' => '20px'
                    ]
                ],
                'cardexpireyear' => [
                    'selector' => 'cardexpireyear',
                    'type' => 'select',
                    'size' => '5',
                    'maxlength' => '4',
                    'iframe' => [
                        'width' => '120px',
                        'height' => '20px'
                    ]
                ]
            ],
            'defaultStyle' => [
                'input' => '',
                'select' => 'width:100px;',
                'iframe' => [
                    'width' => '365px',
                    'height' => '30px'
                ]
            ],
            'error' => 'errorOutput',
            'language' => 'de'
        ];
        $this->assertEquals($expected, $result);
    }
}
