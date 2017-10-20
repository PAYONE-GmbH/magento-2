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

namespace Payone\Core\Test\Unit\Block\Adminhtml\Config\Form\Field;

use Payone\Core\Block\Adminhtml\Config\Form\Field\StatusMapping as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Payone\Core\Model\Source\TransactionStatus;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Multiselect;
use Magento\Framework\Data\Form\AbstractForm;
use Payone\Core\Helper\Toolkit;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class StatusMappingTest extends BaseTestCase
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
     * @var Multiselect|\PHPUnit_Framework_MockObject_MockObject
     */
    private $element;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $form = $this->getMockBuilder(AbstractForm::class)->disableOriginalConstructor()->getMock();

        $element = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->setMethods(['setForm', 'getForm', 'setName', 'setHtmlId', 'setValues'])
            ->getMock();
        $element->method('getForm')->willReturn($form);

        $elementFactory = $this->getMockBuilder(Factory::class)->disableOriginalConstructor()->getMock();
        $elementFactory->method('create')->willReturn($element);

        $orderStatus = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $orderStatus->method('toOptionArray')->willReturn([
            ['value' => 'complete', 'label' => 'Complete']
        ]);

        $transactionStatus = $this->getMockBuilder(TransactionStatus::class)->disableOriginalConstructor()->getMock();
        $transactionStatus->method('toOptionArray')->willReturn([
            ['value' => 'paid', 'label' => 'PAID']
        ]);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'elementFactory' => $elementFactory,
            'orderStatus' => $orderStatus,
            'transactionStatus' => $transactionStatus
        ]);

        $form = $this->objectManager->getObject(AbstractForm::class);
        $this->element = $this->objectManager->getObject(Multiselect::class);
        $this->element->setForm($form);
        $this->classToTest->setElement($this->element);
        $this->classToTest->setForm($form);
        $this->classToTest->addColumn('dummy', ['label' => __('Dummy')]);
    }

    public function testRenderCellTemplate()
    {
        $this->element->setValue([['te<s>t' => 't<e>st', 'data&1' => 'da&ta1']]);

        $result = $this->classToTest->renderCellTemplate('txaction');
        $this->assertNotEmpty($result);

        $result = $this->classToTest->renderCellTemplate('state_status');
        $this->assertNotEmpty($result);

        $result = $this->classToTest->renderCellTemplate('dummy');
        $this->assertNotEmpty($result);
    }

    public function testGetArrayRows()
    {
        $this->element->setValue([['te<s>t' => 't<e>st', 'data&1' => 'da&ta1']]);

        $result = $this->classToTest->getArrayRows();
        $this->assertNotEmpty($result);
    }

    public function testGetArrayRowsSerialized()
    {
        $this->element->setValue(json_encode([['te<s>t' => 't<e>st', 'data&1' => 'da&ta1']]));

        $result = $this->classToTest->getArrayRows();
        $this->assertNotEmpty($result);
    }
}
