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

namespace Payone\Core\Test\Unit\Model\Plugins;

use Payone\Core\Model\Plugins\BillingAddressManagement as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Payone\Core\Model\Risk\Addresscheck;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\BillingAddressManagement;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class BillingAddressManagementTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)->disableOriginalConstructor()->getMock();
        $quoteRepository->method('getActive')->willReturn($quote);

        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $addresscheck->method('handleAddressManagement')->willReturn($address);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'quoteRepository' => $quoteRepository,
            'addresscheck' => $addresscheck
        ]);
    }

    public function testBeforeAssign()
    {
        $cartId = '12345';
        $useForShipping = false;

        $source = $this->getMockBuilder(BillingAddressManagement::class)->disableOriginalConstructor()->getMock();
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->beforeAssign($source, $cartId, $address, $useForShipping);
        $this->assertEquals($cartId, $result[0]);
        $this->assertInstanceOf(AddressInterface::class, $result[1]);
        $this->assertEquals($useForShipping, $result[2]);
    }
}
