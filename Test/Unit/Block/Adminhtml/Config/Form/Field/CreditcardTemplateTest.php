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

use Payone\Core\Block\Adminhtml\Config\Form\Field\CreditcardTemplate as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\Element\Multiselect;
use Magento\Framework\Data\Form\AbstractForm;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class CreditcardTemplateTest extends BaseTestCase
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
     * @var Multiselect
     */
    private $element;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);

        $form = $this->objectManager->getObject(AbstractForm::class);

        $this->element = $this->objectManager->getObject(Multiselect::class);
        $this->element->setValue(['Number_height' => '20px']);
        $this->element->setForm($form);

        $this->classToTest->setElement($this->element);
        $this->classToTest->addColumn('dummy', ['label' => __('Dummy')]);
        $this->classToTest->setForm($form);
    }

    public function testGetCCFields()
    {
        $result = $this->classToTest->getCCFields();
        $expected = ['Number', 'CVC', 'Month', 'Year'];
        $this->assertEquals($expected, $result);
    }

    public function testGetCCStyles()
    {
        $result = $this->classToTest->getCCStyles();
        $expected = ['standard' => 'Standard', 'custom' => 'Custom'];
        $this->assertEquals($expected, $result);
    }

    public function testGetCCTypes()
    {
        $result = $this->classToTest->getCCTypes('Year');
        $expected = ['select' => 'Select', 'tel' => 'Numeric', 'password' => 'Password', 'text' => 'Text'];
        $this->assertEquals($expected, $result);
    }

    public function testFcpoGetValue()
    {
        $result = $this->classToTest->fcpoGetValue('Number_height');
        $expected = '20px';
        $this->assertEquals($expected, $result);

        $result = $this->classToTest->fcpoGetValue('CVC_iframe');
        $expected = 'standard';
        $this->assertEquals($expected, $result);
    }

    public function testFcpoGetValueNoArray()
    {
        $this->element->setValue(json_encode(['Number_height' => '40px']));
        $result = $this->classToTest->fcpoGetValue('Number_height');
        $expected = '40px';
        $this->assertEquals($expected, $result);
    }
}
