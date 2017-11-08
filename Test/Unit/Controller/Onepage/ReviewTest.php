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

namespace Payone\Core\Test\Unit\Controller\Onepage;

use Magento\Quote\Model\Quote;
use Payone\Core\Controller\Onepage\Review as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Quote\Api\Data\CartExtension;
use Magento\Quote\Model\ShippingAssignment;
use Magento\Quote\Model\Shipping;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;


class ReviewTest extends BaseTestCase
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
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSession;

    /**
     * @var Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->request = $this->getMockBuilder(Http::class)->disableOriginalConstructor()->getMock();
        $this->request->method('getParam')->willReturn('free');

        $redirect = $this->getMockBuilder(Redirect::class)->disableOriginalConstructor()->getMock();
        $redirect->method('setPath')->willReturn($redirect);

        $resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $resultFactory->method('create')->willReturn($redirect);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingMethod', 'setShippingMethod'])
            ->getMock();
        $address->method('getShippingMethod')->willReturn('not_free');
        $address->method('setShippingMethod')->willReturn($address);

        $shipping = $this->getMockBuilder(Shipping::class)->disableOriginalConstructor()->getMock();

        $assignment = $this->getMockBuilder(ShippingAssignment::class)->disableOriginalConstructor()->getMock();
        $assignment->method('getShipping')->willReturn($shipping);

        $cartExtension = $this->getMockBuilder(CartExtension::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAssignments'])
            ->getMock();
        $cartExtension->method('getShippingAssignments')->willReturn([$assignment]);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getIsVirtual')->willReturn(false);
        $quote->method('getExtensionAttributes')->willReturn($cartExtension);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getPayoneWorkorderId'])
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $page = $this->getMockBuilder(Page::class)->disableOriginalConstructor()->getMock();

        $pageFactory = $this->getMockBuilder(PageFactory::class)->disableOriginalConstructor()->getMock();
        $pageFactory->method('create')->willReturn($page);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'checkoutSession' => $this->checkoutSession,
            'pageFactory' => $pageFactory
        ]);
    }

    public function testExecute()
    {
        $this->checkoutSession->method('getPayoneWorkorderId')->willReturn(null);

        $this->request->method('getBeforeForwardInfo')->willReturn(false);
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteRedirect()
    {
        $this->checkoutSession->method('getPayoneWorkorderId')->willReturn('12345');

        $this->request->method('getBeforeForwardInfo')->willReturn(false);
        $result = $this->classToTest->execute();
        $this->assertInstanceOf(Page::class, $result);
    }
}
