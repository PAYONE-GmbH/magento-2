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

namespace Payone\Core\Test\Unit\Model\Risk;

use Payone\Core\Helper\Shop;
use Payone\Core\Model\Risk\Addresscheck as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Payone\Core\Model\Api\Request\Addresscheck;
use Payone\Core\Helper\Database;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Helper\Checkout;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;

#[AllowMockObjectsWithoutExpectations]
class AddresscheckTest extends BaseTestCase
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
     * @var Addresscheck|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addresscheck;

    /**
     * @var Database|\PHPUnit_Framework_MockObject_MockObject
     */
    private $databaseHelper;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var Toolkit
     */
    private $toolkitHelper;

    /**
     * @var CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepository;

    /**
     * @var array
     */
    private $checkoutSessionData;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $this->databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isVirtual'])
            ->getMock();
        $this->quote->method('isVirtual')->willReturn(false);
        $this->quote->setData('subtotal', 100);

        $shopHelper = $this->getMockBuilder(Shop::class)->disableOriginalConstructor()->getMock();
        $shopHelper->method('getMagentoVersion')->willReturn("2.4.4");

        $this->toolkitHelper = $this->objectManager->getObject(Toolkit::class, [
            'shopHelper' => $shopHelper
        ]);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $this->checkoutSessionData = [
            'getPayoneShippingAddresscheckScore' => null,
        ];
        $checkoutSession->method('__call')->willReturnCallback(function ($method, $args) use (&$checkoutSession) {
            if (strpos($method, 'set') === 0) {
                $this->checkoutSessionData['get'.substr($method, 3)] = $args[0];
                return $checkoutSession;
            }
            if (array_key_exists($method, $this->checkoutSessionData)) {
                return $this->checkoutSessionData[$method];
            }
            return strpos($method, 'get') === 0 ? null : $checkoutSession;
        });

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)->disableOriginalConstructor()->getMock();
        $this->quoteRepository->method('getActive')->willReturn($this->quote);

        $checkoutHelper = $this->getMockBuilder(Checkout::class)->disableOriginalConstructor()->getMock();
        $checkoutHelper->method('getHashFromAddress')->willReturnCallback(function ($oAddress, $aResponse = false) {
            return $aResponse === false ? 'hash_of_address' : 'hash_of_corrected_address';
        });

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'addresscheck' => $this->addresscheck,
            'databaseHelper' => $this->databaseHelper,
            'toolkitHelper' => $this->toolkitHelper,
            'checkoutSession' => $checkoutSession,
            'quoteRepository' => $this->quoteRepository,
            'checkoutHelper' => $checkoutHelper
        ]);
    }

    public function testAddScoreToSession()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet'])->getMock();
        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'INVALID']);

        $this->classToTest->addScoreToSession($address, ['status' => 'INVALID'], true);

        $this->assertEquals('R', $this->checkoutSessionData['getPayoneBillingAddresscheckScore']);
        $this->assertEquals(
            ['hash_of_address', 'hash_of_corrected_address'],
            $this->checkoutSessionData['getPayoneBillingAddresscheckHashes']
        );
    }

    public function testHandleAddressManagementSessionScoreCorrectionAccepted()
    {
        // the assigned address has the hash of the corrected address from the check
        $this->checkoutSessionData['getPayoneBillingAddresscheckScore'] = 'G';
        $this->checkoutSessionData['getPayoneBillingAddresscheckHashes'] = ['hash_of_original_address', 'hash_of_address'];

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet'])->getMock();
        $this->addresscheck->expects($this->never())->method('sendRequest');

        $result = $this->classToTest->handleAddressManagement($address, $this->quote, true);
        $this->assertEquals('G', $result->getPayoneAddresscheckScore());
    }

    public function testHandleAddressManagementSessionScoreCorrectionDeclined()
    {
        // the assigned address has the hash of the original address from the check
        $this->checkoutSessionData['getPayoneBillingAddresscheckScore'] = 'G';
        $this->checkoutSessionData['getPayoneBillingAddresscheckHashes'] = ['hash_of_address', 'hash_of_corrected_address'];

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet'])->getMock();
        $this->addresscheck->expects($this->never())->method('sendRequest');

        $result = $this->classToTest->handleAddressManagement($address, $this->quote, true);
        $this->assertEquals('G', $result->getPayoneAddresscheckScore());
    }

    public function testHandleAddressManagementSessionScoreOtherAddress()
    {
        $this->checkoutSessionData['getPayoneBillingAddresscheckScore'] = 'G';
        $this->checkoutSessionData['getPayoneBillingAddresscheckHashes'] = ['hash_of_other_address', 'hash_of_other_corrected_address'];

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getStreet'])->getMock();
        $this->addresscheck->expects($this->once())->method('sendRequest')->willReturn(['status' => 'VALID', 'personstatus' => 'NONE']);

        $result = $this->classToTest->handleAddressManagement($address, $this->quote, true);
        $this->assertEquals('G', $result->getPayoneAddresscheckScore());
    }

    public function testIsCheckNeededForQuoteMinBasket()
    {
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([['min_order_total', 'address_check', 'payone_protect', null, 10]]);

        $result = $this->classToTest->isCheckNeededForQuote(true, true, 5);
        $this->assertFalse($result);
    }

    public function testIsCheckNeededForQuoteMaxBasket()
    {
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([['max_order_total', 'address_check', 'payone_protect', null, 10]]);

        $result = $this->classToTest->isCheckNeededForQuote(true, true, 15);
        $this->assertFalse($result);
    }

    public function testIsCheckNeededForQuoteVirtual()
    {
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([['check_billing_for_virtual_order', 'address_check', 'payone_protect', null, 0]]);

        $result = $this->classToTest->isCheckNeededForQuote(true, true, 15);
        $this->assertFalse($result);
    }

    public function testIsCheckNeededForQuote()
    {
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([['check_billing_for_virtual_order', 'address_check', 'payone_protect', null, 0]]);

        $result = $this->classToTest->isCheckNeededForQuote(true, false, 15);
        $this->assertTrue($result);
    }

    public function testCorrectAddress()
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $this->addresscheck->method('sendRequest')->willReturn(true);

        $result = $this->classToTest->correctAddress($address);
        $this->assertEquals($address, $result);
    }

    public function testHandleAddressManagement()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->setData('payone_addresscheck_score', null);
        $address->method('getStreet')->willReturn(['Teststr. 12', '3rd floor']);

        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'VALID', 'street' => 'Another str. 7', 'firstname' => 'Patrick']);

        $result = $this->classToTest->handleAddressManagement($address, $this->quote, false);
        $this->assertInstanceOf(Address::class, $result);

        $result = $this->classToTest->isAddressCorrected();
        $this->assertTrue($result);
    }

    public function testHandleAddressManagementExceptionError()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->setData('payone_addresscheck_score', null);

        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'ERROR']);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['handle_response_error', 'address_check', 'payone_protect', null, 'stop_checkout'],
                ['stop_checkout_message', 'address_check', 'payone_protect', null, null]
            ]);

        $this->expectException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $this->quote, false);
    }

    public function testHandleAddressManagementExceptionInvalid()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->setData('payone_addresscheck_score', null);

        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'INVALID', 'customermessage' => 'Address invalid']);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['message_response_invalid', 'address_check', 'payone_protect', null, null]
            ]);

        $this->expectException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $this->quote, false);
    }

    public function testHandleAddressManagementExceptionNoStatus()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->setData('payone_addresscheck_score', null);

        $this->addresscheck->method('sendRequest')->willReturn([]);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['message_response_invalid', 'address_check', 'payone_protect', null, null]
            ]);

        $this->expectException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $this->quote, false);
    }

    public function testHandleAddressManagementExceptionInvalidDefault()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->setData('payone_addresscheck_score', null);

        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'INVALID', 'customermessage' => 'Address invalid']);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['message_response_invalid', 'address_check', 'payone_protect', null, 'Address invalid: {{payone_customermessage}}']
            ]);

        $this->expectException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $this->quote, false);
    }

    public function testGetScoreR()
    {
        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'INVALID']);
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getScore($address);
        $expected = 'R';
        $this->assertEquals($expected, $result);
    }

    public function testGetScorePersonstatusNoMapping()
    {
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([['mapping', 'personstatus', 'payone_protect', null, $this->toolkitHelper->serialize([])]]);

        $this->addresscheck->method('sendRequest')->willReturn(['personstatus' => 'ABC']);
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getScore($address);
        $expected = 'G';
        $this->assertEquals($expected, $result);
    }

    public function testGetScorePersonstatus()
    {
        $status = [
            ['personstatus' => 'ABC', 'score' => 'D']
        ];
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([['mapping', 'personstatus', 'payone_protect', null, $this->toolkitHelper->serialize($status)]]);

        $this->addresscheck->method('sendRequest')->willReturn(['personstatus' => 'ABC']);
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getScore($address);
        $expected = 'D';
        $this->assertEquals($expected, $result);
    }

    public function testGetScoreStillValid()
    {
        $this->addresscheck->method('sendRequest')->willReturn(true);
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $expected = 'X';
        $this->databaseHelper->method('getOldAddressStatus')->willReturn($expected);

        $result = $this->classToTest->getScore($address);
        $this->assertEquals($expected, $result);
    }

    public function testGetResponse()
    {
        $this->addresscheck->method('sendRequest')->willReturn(true);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->getResponse($address);
        $this->assertTrue($result);
    }

    public function testGetErrorMessageByResponse()
    {
        $aResponse = ['wrongCountry' => 1];
        $result = $this->classToTest->getErrorMessageByResponse($aResponse);
        $this->assertFalse($result);
    }
}
