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

namespace Payone\Core\Model\Plugins;

use Payone\Core\Model\PayoneConfig;
use Magento\Payment\Model\MethodInterface;

class PaymentHelper
{
    const XML_PATH_PAYMENT_METHODS = 'payment';

    /**
     * Array of all Payone klarna payment method codes
     *
     * @var array
     */
    protected $payoneKlarnaMethods = [
        PayoneConfig::METHOD_KLARNA_BASE,
        PayoneConfig::METHOD_KLARNA_INVOICE,
        PayoneConfig::METHOD_KLARNA_DEBIT,
        PayoneConfig::METHOD_KLARNA_INSTALLMENT,
    ];

    /**
     * Factory for payment method models
     *
     * @var \Magento\Payment\Model\Method\Factory
     */
    protected $_methodFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory
    ) {
        $this->_methodFactory = $paymentMethodFactory;
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Get config name of method model
     *
     * @param string $code
     * @return string
     */
    protected function getMethodModelConfigName($code)
    {
        return sprintf('%s/%s/model', self::XML_PATH_PAYMENT_METHODS, $code);
    }

    /**
     * Retrieve method model object
     *
     * @param string $code
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return MethodInterface
     */
    protected function getCoreMethodInstance($code)
    {
        $class = $this->scopeConfig->getValue(
            $this->getMethodModelConfigName($code),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$class) {
            throw new \UnexpectedValueException('Payment model name is not provided in config!');
        }

        return $this->_methodFactory->create($class);
    }

    /**
     * Retrieve method model object
     *
     * This plugin is necessary because the Klarna module "\Klarna\Kp\Plugin\Payment\Helper\DataPlugin" claims every
     * payment method with "klarna_" in the payment code for it's own payment model which blocks others to use such a payment code
     *
     * @param \Magento\Payment\Helper\Data $subject
     * @param callable                     $proceed
     * @param string                       $code
     * @return MethodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetMethodInstance(\Magento\Payment\Helper\Data $subject, callable $proceed, $code)
    {
        if (!in_array($code, $this->payoneKlarnaMethods)) {
            return $proceed($code);
        }
        return $this->getCoreMethodInstance($code);
    }
}
