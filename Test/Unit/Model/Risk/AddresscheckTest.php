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

use Payone\Core\Model\Risk\Addresscheck as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Payone\Core\Model\Api\Request\Addresscheck;
use Payone\Core\Helper\Database;
use Payone\Core\Helper\Toolkit;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;

class ForwardingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
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

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->addresscheck = $this->getMockBuilder(Addresscheck::class)->disableOriginalConstructor()->getMock();
        $this->databaseHelper = $this->getMockBuilder(Database::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper = $this->getMockBuilder(Toolkit::class)->disableOriginalConstructor()->getMock();
        $toolkitHelper->method('handleSubstituteReplacement')->willReturn('Invalid message');
        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPayoneBillingAddresscheckScore',
                'getPayoneShippingAddresscheckScore',
                'unsPayoneBillingAddresscheckScore',
                'unsPayoneShippingAddresscheckScore'])
            ->getMock();
        $checkoutSession->method('getPayoneShippingAddresscheckScore')->willReturn(null);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'addresscheck' => $this->addresscheck,
            'databaseHelper' => $this->databaseHelper,
            'toolkitHelper' => $toolkitHelper,
            'checkoutSession' => $checkoutSession
        ]);
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
            ->setMethods(['getPayoneAddresscheckScore', 'setPayoneAddresscheckScore', 'getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn(null);
        $address->method('getStreet')->willReturn(['Teststr. 12', '3rd floor']);
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getSubtotal')->willReturn(100);
        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'VALID', 'street' => 'Another str. 7', 'firstname' => 'Patrick']);

        $result = $this->classToTest->handleAddressManagement($address, $quote, false);
        $this->assertInstanceOf(Address::class, $result);

        $result = $this->classToTest->isAddressCorrected();
        $this->assertTrue($result);
    }

    public function testHandleAddressManagementExceptionError()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneAddresscheckScore', 'setPayoneAddresscheckScore', 'getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn(null);
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getSubtotal')->willReturn(100);
        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'ERROR']);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['handle_response_error', 'address_check', 'payone_protect', null, 'stop_checkout'],
                ['stop_checkout_message', 'address_check', 'payone_protect', null, null]
            ]);

        $this->setExpectedException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $quote, false);
    }

    public function testHandleAddressManagementExceptionInvalid()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneAddresscheckScore', 'setPayoneAddresscheckScore', 'getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn(null);
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getSubtotal')->willReturn(100);
        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'INVALID', 'customermessage' => 'Address invalid']);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['message_response_invalid', 'address_check', 'payone_protect', null, null]
            ]);

        $this->setExpectedException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $quote, false);
    }

    public function testHandleAddressManagementExceptionNoStatus()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneAddresscheckScore', 'setPayoneAddresscheckScore', 'getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn(null);
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getSubtotal')->willReturn(100);
        $this->addresscheck->method('sendRequest')->willReturn([]);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['message_response_invalid', 'address_check', 'payone_protect', null, null]
            ]);

        $this->setExpectedException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $quote, false);
    }

    public function testHandleAddressManagementExceptionInvalidDefault()
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayoneAddresscheckScore', 'setPayoneAddresscheckScore', 'getStreet', 'setStreet', 'getData', 'setData'])
            ->getMock();
        $address->method('getPayoneAddresscheckScore')->willReturn(null);
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getSubtotal')->willReturn(100);
        $this->addresscheck->method('sendRequest')->willReturn(['status' => 'INVALID', 'customermessage' => 'Address invalid']);
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([
                ['message_response_invalid', 'address_check', 'payone_protect', null, 'Address invalid: {{payone_customermessage}}']
            ]);

        $this->setExpectedException(LocalizedException::class);
        $this->classToTest->handleAddressManagement($address, $quote, false);
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
        $status = 'x';
        $this->databaseHelper->expects($this->any())
            ->method('getConfigParam')
            ->willReturnMap([['mapping_personstatus', 'address_check', 'payone_protect', null, serialize($status)]]);

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
            ->willReturnMap([['mapping_personstatus', 'address_check', 'payone_protect', null, serialize($status)]]);

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
}
