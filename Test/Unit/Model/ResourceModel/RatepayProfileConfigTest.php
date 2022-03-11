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

namespace Payone\Core\Test\Unit\Model\ResourceModel;

use Payone\Core\Model\PayoneConfig;
use Payone\Core\Model\ResourceModel\RatepayProfileConfig as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class RatepayProfileConfigTest extends BaseTestCase
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
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->connection = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchAll', 'fetchOne', 'select', 'from', 'where', 'insert', 'order', 'limit', 'update'])
            ->getMock();
        $this->connection->method('select')->willReturn($this->connection);
        $this->connection->method('from')->willReturn($this->connection);
        $this->connection->method('where')->willReturn($this->connection);
        $this->connection->method('order')->willReturn($this->connection);
        $this->connection->method('limit')->willReturn($this->connection);

        $resource = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $resource->method('getConnection')->willReturn($this->connection);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getResources')->willReturn($resource);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
        ]);
    }

    public function testGetProfileConfigsByIds()
    {
        $expected = [['shop_id' => '12345']];
        $this->connection->method('fetchAll')->willReturn($expected);

        $result = $this->classToTest->getProfileConfigsByIds(['12345']);
        $this->assertEquals($expected, $result);
    }

    public function testGetAllProfileConfigs()
    {
        $expected = [['shop_id' => '12345']];
        $this->connection->method('fetchAll')->willReturn($expected);

        $result = $this->classToTest->getAllProfileConfigs();
        $this->assertEquals($expected, $result);
    }

    public function testProfileExists()
    {
        $return = [['shop_id' => '12345']];
        $this->connection->method('fetchAll')->willReturn($return);

        $result = $this->classToTest->profileExists('12345');
        $this->assertTrue($result);
    }

    public function testProfileExistsFalse()
    {
        $return = [];
        $this->connection->method('fetchAll')->willReturn($return);

        $result = $this->classToTest->profileExists('112233');
        $this->assertFalse($result);
    }

    private function getProfileResponse()
    {
        $aProfileResponse = [];
        $aProfileResponse['add_paydata[profile-id]'] = '1';
        $aProfileResponse['add_paydata[merchant-name]'] = '1';
        $aProfileResponse['add_paydata[merchant-status]'] = '1';
        $aProfileResponse['add_paydata[shop-name]'] = '1';
        $aProfileResponse['add_paydata[name]'] = '1';
        $aProfileResponse['add_paydata[currency]'] = '1';
        $aProfileResponse['add_paydata[type]'] = '1';
        $aProfileResponse['add_paydata[activation-status-elv]'] = '1';
        $aProfileResponse['add_paydata[activation-status-installment]'] = '1';
        $aProfileResponse['add_paydata[activation-status-invoice]'] = '1';
        $aProfileResponse['add_paydata[activation-status-prepayment]'] = '1';
        $aProfileResponse['add_paydata[amount-min-longrun]'] = '1';
        $aProfileResponse['add_paydata[b2b-PQ-full]'] = '1';
        $aProfileResponse['add_paydata[b2b-PQ-light]'] = '1';
        $aProfileResponse['add_paydata[b2b-elv]'] = '1';
        $aProfileResponse['add_paydata[b2b-installment]'] = '1';
        $aProfileResponse['add_paydata[b2b-invoice]'] = '1';
        $aProfileResponse['add_paydata[b2b-prepayment]'] = '1';
        $aProfileResponse['add_paydata[country-code-billing]'] = '1';
        $aProfileResponse['add_paydata[country-code-delivery]'] = '1';
        $aProfileResponse['add_paydata[delivery-address-PQ-full]'] = '1';
        $aProfileResponse['add_paydata[delivery-address-PQ-light]'] = '1';
        $aProfileResponse['add_paydata[delivery-address-elv]'] = '1';
        $aProfileResponse['add_paydata[delivery-address-installment]'] = '1';
        $aProfileResponse['add_paydata[delivery-address-invoice]'] = '1';
        $aProfileResponse['add_paydata[delivery-address-prepayment]'] = '1';
        $aProfileResponse['add_paydata[device-fingerprint-snippet-id]'] = '1';
        $aProfileResponse['add_paydata[eligibility-device-fingerprint]'] = '1';
        $aProfileResponse['add_paydata[eligibility-ratepay-elv]'] = '1';
        $aProfileResponse['add_paydata[eligibility-ratepay-installment]'] = '1';
        $aProfileResponse['add_paydata[eligibility-ratepay-invoice]'] = '1';
        $aProfileResponse['add_paydata[eligibility-ratepay-pq-full]'] = '1';
        $aProfileResponse['add_paydata[eligibility-ratepay-pq-light]'] = '1';
        $aProfileResponse['add_paydata[eligibility-ratepay-prepayment]'] = '1';
        $aProfileResponse['add_paydata[interest-rate-merchant-towards-bank]'] = '1';
        $aProfileResponse['add_paydata[interestrate-default]'] = '1';
        $aProfileResponse['add_paydata[interestrate-max]'] = '1';
        $aProfileResponse['add_paydata[interestrate-min]'] = '1';
        $aProfileResponse['add_paydata[min-difference-dueday]'] = '1';
        $aProfileResponse['add_paydata[month-allowed]'] = '1';
        $aProfileResponse['add_paydata[month-longrun]'] = '1';
        $aProfileResponse['add_paydata[month-number-max]'] = '1';
        $aProfileResponse['add_paydata[month-number-min]'] = '1';
        $aProfileResponse['add_paydata[payment-amount]'] = '1';
        $aProfileResponse['add_paydata[payment-firstday]'] = '1';
        $aProfileResponse['add_paydata[payment-lastrate]'] = '1';
        $aProfileResponse['add_paydata[rate-min-longrun]'] = '1';
        $aProfileResponse['add_paydata[rate-min-normal]'] = '1';
        $aProfileResponse['add_paydata[service-charge]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-elv-max]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-elv-min]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-installment-max]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-installment-min]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-invoice-max]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-invoice-min]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-prepayment-max]'] = '1';
        $aProfileResponse['add_paydata[tx-limit-prepayment-min]'] = '1';
        $aProfileResponse['add_paydata[valid-payment-firstdays]'] = '1';
        return $aProfileResponse;
    }

    public function testUpdateProfileConfig()
    {
        $result = $this->classToTest->updateProfileConfig('12345', $this->getProfileResponse());
        $this->assertNull($result);
    }

    public function testInsertProfileConfig()
    {
        $result = $this->classToTest->insertProfileConfig('12345', $this->getProfileResponse());
        $this->assertNull($result);
    }

    public function testGetMatchingShopId()
    {
        $expected = '12345';
        $this->connection->method('fetchOne')->willReturn($expected);
        $result = $this->classToTest->getMatchingShopId(PayoneConfig::METHOD_RATEPAY_INVOICE, ['12345'], 'DE', 'EUR', 50);
        $this->assertEquals($expected, $result);
    }

    public function testGetMatchingShopIdFalse()
    {
        $expected = false;
        $this->connection->method('fetchOne')->willReturn($expected);
        $result = $this->classToTest->getMatchingShopId('not_matching', ['12345'], 'DE', 'EUR', 50);
        $this->assertEquals($expected, $result);
    }
}
