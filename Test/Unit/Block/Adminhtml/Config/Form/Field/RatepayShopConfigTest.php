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

namespace Payone\Core\Test\Unit\Block\Adminhtml\Config\Form\Field;

use Payone\Core\Block\Adminhtml\Config\Form\Field\RatepayShopConfig as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Multiselect;
use Magento\Framework\Data\Form\AbstractForm;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;

class RatepayShopConfig extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $form = $this->getMockBuilder(AbstractForm::class)->disableOriginalConstructor()->getMock();

        $element = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->setMethods(['setForm', 'getForm', 'setName', 'setHtmlId', 'setValues', 'getElementHtml'])
            ->getMock();
        $element->method('getForm')->willReturn($form);
        $element->method('getElementHtml')->willReturn('html');

        $elementFactory = $this->getMockBuilder(Factory::class)->disableOriginalConstructor()->getMock();
        $elementFactory->method('create')->willReturn($element);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'elementFactory' => $elementFactory,
        ]);

        $form = $this->objectManager->getObject(AbstractForm::class);
        $element = $this->objectManager->getObject(Multiselect::class);
        $element->setValue([['te<s>t' => 't<e>st', 'data&1' => 'da&ta1']]);
        $element->setForm($form);
        $this->classToTest->setElement($element);
        $this->classToTest->setForm($form);
    }

    public function testRenderCellTemplate()
    {
        $result = $this->classToTest->renderCellTemplate('shop_id');
        $this->assertNotEmpty($result);
    }
}
