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

class HostedIframeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
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

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $paymentHelper->method('isCheckCvcActive')->willReturn(true);

        $this->hostedIframe = $this->objectManager->getObject(HostedIframe::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'paymentHelper' => $paymentHelper
        ]);
    }

    public function testGetHostedFieldConfigEmpty()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/creditcard/cc_template', ScopeInterface::SCOPE_STORE, null, serialize('string')]
                ]
            );
        $result = $this->hostedIframe->getHostedFieldConfig();
        $expected = [];
        $this->assertEquals($expected, $result);
    }

    public function testGetHostedFieldConfig()
    {
        $sHostedConfig = 'a:32:{s:11:"Number_type";s:3:"tel";s:12:"Number_count";s:2:"30";s:10:"Number_max";s:2:"16";s:13:"Number_iframe";s:8:"standard";s:12:"Number_style";s:6:"custom";s:10:"Number_css";s:0:"";s:8:"CVC_type";s:3:"tel";s:9:"CVC_count";s:2:"30";s:7:"CVC_max";s:1:"4";s:10:"CVC_iframe";s:8:"standard";s:9:"CVC_style";s:6:"custom";s:7:"CVC_css";s:0:"";s:10:"Month_type";s:6:"select";s:11:"Month_count";s:1:"3";s:9:"Month_max";s:1:"2";s:12:"Month_iframe";s:6:"custom";s:11:"Month_width";s:5:"120px";s:12:"Month_height";s:4:"20px";s:11:"Month_style";s:8:"standard";s:9:"Year_type";s:6:"select";s:10:"Year_count";s:1:"5";s:8:"Year_max";s:1:"4";s:11:"Year_iframe";s:6:"custom";s:10:"Year_width";s:5:"120px";s:11:"Year_height";s:4:"20px";s:10:"Year_style";s:8:"standard";s:14:"Standard_input";s:0:"";s:18:"Standard_selection";s:12:"width:100px;";s:12:"Iframe_width";s:5:"365px";s:13:"Iframe_height";s:4:"30px";s:13:"Errors_active";s:4:"true";s:11:"Errors_lang";s:2:"de";}';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_general/creditcard/cc_template', ScopeInterface::SCOPE_STORE, null, $sHostedConfig]
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
