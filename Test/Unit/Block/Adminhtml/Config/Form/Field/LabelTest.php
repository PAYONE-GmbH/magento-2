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
 * PHP version 7
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Block\Adminhtml\Config\Form\Field;

use Payone\Core\Block\Adminhtml\Config\Form\Field\Label as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Payone\Core\Helper\Base;
use Magento\Framework\View\LayoutInterface;

class LabelTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var Base|PayoneObjectManager
     */
    private $baseHelper;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = $this->getObjectManager();

        $this->baseHelper = $this->getMockBuilder(Base::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'baseHelper' => $this->baseHelper
        ]);
    }

    protected function getElementMock()
    {
        $element = $this->getMockBuilder(AbstractElement::class)
            ->setConstructorArgs([
                $this->objectManager->getObject(\Magento\Framework\Data\Form\Element\Factory::class),
                $this->objectManager->getObject(\Magento\Framework\Data\Form\Element\CollectionFactory::class),
                $this->objectManager->getObject(\Magento\Framework\Escaper::class),
                []
            ])
            ->setMethods([
                'unsScope',
                'unsCanUseWebsiteValue',
                'unsCanUseDefaultValue',
                'getHtmlId',
                'getName',
                'getLabel',
                'getComment',
                'getOriginalData'
            ])
            ->getMock();
        $element->method('unsScope')->willReturn($element);
        $element->method('unsCanUseWebsiteValue')->willReturn($element);
        $element->method('unsCanUseDefaultValue')->willReturn($element);
        $element->method('getHtmlId')->willReturn('test');
        $element->method('getName')->willReturn('test');
        $element->method('getComment')->willReturn('comment');
        $element->method('getLabel')->willReturn('test');
        $element->method('getOriginalData')->willReturn(['path' => 'payone_payment/ratepay_invoice']);
        return $element;
    }

    public function testRender()
    {
        $this->baseHelper->method("getConfigParamByPath")->willReturn(false);

        $element = $this->getElementMock();

        $result = $this->classToTest->render($element);
        $this->assertNotEmpty($result);
    }

    public function testRenderEmpty()
    {
        $this->baseHelper->method("getConfigParamByPath")->willReturn("req");

        $element = $this->getElementMock();

        $result = $this->classToTest->render($element);
        $this->assertEmpty($result);
    }
}
