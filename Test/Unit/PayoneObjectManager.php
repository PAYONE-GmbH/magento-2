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

namespace Payone\Core\Test\Unit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PayoneObjectManager extends ObjectManager
{
    /**
     * Class constructor
     *
     * @param \PHPUnit_Framework_TestCase $testObject
     */
    public function __construct($testObject)
    {
        $this->_testObject = $testObject;
    }

    /**
     * Retrieve specific mock of core resource model
     *
     * @return \Magento\Framework\Module\ResourceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getResourceModelMock()
    {
        $resourceMock = $this->_testObject->getMockBuilder(\Magento\Framework\Module\ModuleResource::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->setMethods(['getIdFieldName', '__sleep', '__wakeup'])
            ->getMock();
        $resourceMock->expects(
            $this->_testObject->any()
        )->method(
            'getIdFieldName'
        )->will(
            $this->_testObject->returnValue('id')
        );

        return $resourceMock;
    }

    /**
     * Retrieve associative array of arguments that used for new object instance creation
     *
     * @param string $className
     * @param array $arguments
     * @return array
     */
    public function getConstructArguments($className, array $arguments = [])
    {
        $constructArguments = [];
        if (!method_exists($className, '__construct')) {
            return $constructArguments;
        }
        $method = new \ReflectionMethod($className, '__construct');

        foreach ($method->getParameters() as $parameter) {
            $parameterName = $parameter->getName();
            $argClassName = null;
            $defaultValue = null;

            if (array_key_exists($parameterName, $arguments)) {
                $constructArguments[$parameterName] = $arguments[$parameterName];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $defaultValue = $parameter->getDefaultValue();
            }

            try {
                if ($parameter->getClass()) {
                    $argClassName = $parameter->getClass()->getName();
                }
                $object = $this->_getMockObject($argClassName, $arguments);
            } catch (\ReflectionException $e) {
                $parameterString = $parameter->__toString();
                $firstPosition = strpos($parameterString, '<required>');
                if ($firstPosition !== false) {
                    $parameterString = substr($parameterString, $firstPosition + 11);
                    $parameterString = substr($parameterString, 0, strpos($parameterString, ' '));
                    $object = $this->_testObject->getMockBuilder($parameterString)
                        ->disableOriginalConstructor()
                        ->disableOriginalClone()
                        ->disableArgumentCloning()
                        ->getMock();
                }
            }

            $constructArguments[$parameterName] = null === $object ? $defaultValue : $object;
        }
        return $constructArguments;
    }

    /**
     * Get mock without call of original constructor
     *
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockWithoutConstructorCall($className)
    {
        $mock = $this->_testObject->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock();
        return $mock;
    }

    /**
     * Helper function that creates a mock object for a given class name.
     *
     * Will return a real object in some cases to assist in testing.
     *
     * @param string $argClassName
     * @param array $arguments
     * @return null|object|\PHPUnit_Framework_MockObject_MockObject
     */
    private function _getMockObject($argClassName, array $arguments)
    {
        if (is_subclass_of($argClassName, '\Magento\Framework\Api\ExtensibleObjectBuilder')) {
            $object = $this->getBuilder($argClassName, $arguments);
            return $object;
        } else {
            $object = $this->_createArgumentMock($argClassName, $arguments);
            return $object;
        }
    }
}
