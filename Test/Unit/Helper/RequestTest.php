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

use Payone\Core\Helper\Request;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Payone\Core\Helper\Environment;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\PayoneConfig;
use Locale;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class RequestTest extends BaseTestCase
{
    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $encoding = 'UTF-8';

    /**
     * @var string
     */
    private $version = '1.2.3';

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $this->objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn(null);

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $environmentHelper = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $environmentHelper->method('getEncoding')->willReturn($this->encoding);

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getMagentoVersion')->willReturn($this->version);
        $shopHelper->method('getLocale')->willReturn('de');

        $this->request = $this->objectManager->getObject(Request::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'environmentHelper' => $environmentHelper,
            'shopHelper' => $shopHelper
        ]);
    }

    public function testGetBankaccountCheckRequest()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/check_bankaccount', ScopeInterface::SCOPE_STORE, null, '1'],
                    ['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/mode', ScopeInterface::SCOPE_STORE, null, 'live'],
                    ['payone_general/global/mid', ScopeInterface::SCOPE_STORE, null, '12345'],
                    ['payone_general/global/aid', ScopeInterface::SCOPE_STORE, null, '54321'],
                    ['payone_general/global/portalid', ScopeInterface::SCOPE_STORE, null, '0815'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, null, 'abcde'],
                    ['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/bankaccountcheck_type', ScopeInterface::SCOPE_STORE, null, '1'],
                ]
            );

        $result = $this->request->getBankaccountCheckRequest();
        $expected = [
            'request' => 'bankaccountcheck',
            'responsetype' => 'JSON',
            'mode' => 'live',
            'mid' => '12345',
            'aid' => '54321',
            'portalid' => '0815',
            'encoding' => $this->encoding,
            'language' => 'de',
            'checktype' => '1',
            'hash' => $this->request->getBankaccountCheckRequestHash(),
            'integrator_name' => 'Magento2',
            'integrator_version' => $this->version,
            'solution_name' => 'fatchip',
            'solution_version' => PayoneConfig::MODULE_VERSION,
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetBankaccountCheckRequestEmpty()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([['payone_payment/'.PayoneConfig::METHOD_DEBIT.'/check_bankaccount', ScopeInterface::SCOPE_STORE, null, '0']]);
        $result = $this->request->getBankaccountCheckRequest();
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    public function testGetHostedIframeRequest()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['payone_payment/'.PayoneConfig::METHOD_CREDITCARD.'/mode', ScopeInterface::SCOPE_STORE, null, 'live'],
                    ['payone_general/global/mid', ScopeInterface::SCOPE_STORE, null, '12345'],
                    ['payone_general/global/aid', ScopeInterface::SCOPE_STORE, null, '54321'],
                    ['payone_general/global/portalid', ScopeInterface::SCOPE_STORE, null, '0815'],
                    ['payone_general/global/key', ScopeInterface::SCOPE_STORE, null, 'abcde'],
                ]
            );

        $result = $this->request->getHostedIframeRequest();
        $expected = [
            'request' => 'creditcardcheck',
            'responsetype' => 'JSON',
            'mode' => 'live',
            'mid' => '12345',
            'aid' => '54321',
            'portalid' => '0815',
            'encoding' => $this->encoding,
            'storecarddata' => 'yes',
            'hash' => $this->request->getHostedIframeRequestCCHash()
        ];
        $this->assertEquals($expected, $result);
    }
}
