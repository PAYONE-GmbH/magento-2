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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Helper;

use Payone\Core\Helper\Ratepay as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Helper\Shop;
use Payone\Core\Model\Api\Request\Genericpayment\Profile;
use Payone\Core\Model\ResourceModel\RatepayProfileConfig;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Customer\Api\Data\CustomerInterface;

class RatepayTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var RatepayProfileConfig
     */
    private $profileResource;

    /**
     * @var Profile
     */
    private $profile;

    /**
     * @var Session
     */
    private $checkoutSession;

    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $context = $objectManager->getObject(Context::class, ['scopeConfig' => $this->scopeConfig]);

        $this->profileResource = $this->getMockBuilder(RatepayProfileConfig::class)->disableOriginalConstructor()->getMock();
        $this->profile = $this->getMockBuilder(Profile::class)->disableOriginalConstructor()->getMock();

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getCode')->willReturn('test');

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getStore')->willReturn($store);

        $customer = $this->getMockBuilder(CustomerInterface::class)->disableOriginalConstructor()->getMock();
        $customer->method('getId')->willReturn('4711');

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getCustomer')->willReturn($customer);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods(['getPayoneRatepayDeviceFingerprintToken', 'setPayoneRatepayDeviceFingerprintToken'])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'storeManager' => $storeManager,
            'profileResource' => $this->profileResource,
            'profile' => $this->profile,
            'checkoutSession' => $this->checkoutSession,
        ]);
    }

    public function testGetRatepayShopConfigIdsByPaymentMethod()
    {
        $aShopConfig = [['shop_id' => '12345']];
        $this->scopeConfig->method('getValue')->willReturn(json_encode($aShopConfig));

        $result = $this->classToTest->getRatepayShopConfigIdsByPaymentMethod('payone_ratepay_invoice');
        $this->assertEquals(['12345'], $result);
    }

    public function testGetPaymentMethodFromPath()
    {
        $expected = "payone_ratepay_invoice";
        $result = $this->classToTest->getPaymentMethodFromPath('payone_payment/'.$expected.'/ratepay_shop_config');
        $this->assertEquals($expected, $result);
    }

    public function testGetPaymentMethodFromPathFalse()
    {
        $result = $this->classToTest->getPaymentMethodFromPath('payone_payment/something/not_found');
        $this->assertFalse($result);
    }

    public function testImportProfileConfiguration()
    {
        $this->profileResource->method('profileExists')->willReturn(false);
        $this->profile->method('sendRequest')->willReturn(['status' => 'OK']);

        $result = $this->classToTest->importProfileConfiguration('12345', 'EUR', 'payone_ratepay_invoice');
        $this->assertNull($result);
    }

    public function testRefreshProfiles()
    {
        $aShopConfig = [['shop_id' => '12345', 'currency' => 'EUR']];
        $this->scopeConfig->method('getValue')->willReturn(json_encode($aShopConfig));
        $this->profile->method('sendRequest')->willReturn(['status' => 'OK']);

        $result = $this->classToTest->refreshProfiles('payone_ratepay_invoice');
        $this->assertNull($result);
    }

    public function testRefreshProfilesException()
    {
        $aShopConfig = [['shop_id' => '12345', 'currency' => 'EUR']];
        $this->scopeConfig->method('getValue')->willReturn(json_encode($aShopConfig));
        $this->profile->method('sendRequest')->willReturn(['status' => 'ERROR']);

        $this->expectException(\Exception::class);
        $this->classToTest->refreshProfiles('payone_ratepay_invoice');
    }

    public function testGetRatepayDeviceFingerprintToken()
    {
        $this->checkoutSession->method('getPayoneRatepayDeviceFingerprintToken')->willReturn(null);

        $result = $this->classToTest->getRatepayDeviceFingerprintToken();
        $this->assertNotEmpty($result);
    }

    public function testGetRatepayShopId()
    {
        $aShopConfig = [['shop_id' => '12345']];
        $this->scopeConfig->method('getValue')->willReturn(json_encode($aShopConfig));

        $expected = '54321';
        $this->profileResource->method('getMatchingShopId')->willReturn($expected);

        $result = $this->classToTest->getRatepayShopId('payone_ratepay_invoice', 'DE', 'EUR', 50);
        $this->assertEquals($expected, $result);
    }

    public function testGetRatepayShopConfigById()
    {
        $aConfig = ['foo' => 'bar'];
        $this->profileResource->method('getProfileConfigsByIds')->willReturn([$aConfig]);

        $result = $this->classToTest->getRatepayShopConfigById("test");

        $this->assertEquals($aConfig, $result);
    }

    public function testGetRatepayShopConfigByIdFalse()
    {
        $this->profileResource->method('getProfileConfigsByIds')->willReturn([]);

        $result = $this->classToTest->getRatepayShopConfigById("test");

        $this->assertFalse($result);
    }
}
