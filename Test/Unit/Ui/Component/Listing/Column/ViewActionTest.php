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

namespace Payone\Core\Test\Unit\Ui\Component\Listing\Column;

use Payone\Core\Ui\Component\Listing\Column\ViewAction as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Model\Test\PayoneObjectManager;

class ViewActionTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $urlBuilder->method('getUrl')->willReturn('http://testdomain.com');

        $request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHeader'])
            ->getMock();
        $request->method('getHeader')->willReturn('html');
        $context = $objectManager->getObject(Context::class, [
            'request' => $request
        ]);

        $data = [
            'name' => 'Name',
            'config' => [
                'viewUrlPath' => 'viewUrlPath',
                'urlEntityParamName' => 'urlEntityParamName',
            ],
        ];

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'context' => $context,
            'data' => $data,
            'urlBuilder' => $urlBuilder
        ]);
    }

    public function testPrepareDataSource()
    {
        $input = [
            'data' => [
                'items' => [
                    ['id' => '12345'],
                ]
            ]
        ];
        $result = $this->classToTest->prepareDataSource($input);
        $expected = 'http://testdomain.com';
        $this->assertEquals($expected, $result['data']['items'][0]['Name']['view']['href']);
    }
}
