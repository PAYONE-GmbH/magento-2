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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Paypal;

use Magento\Framework\View\Element\Template;

class Base extends Template implements \Magento\Catalog\Block\ShortcutInterface
{
    /**
     * Shortcut alias
     *
     * @var string
     */
    protected $alias;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * Locale codes supported by misc images (marks, shortcuts etc)
     *
     * @var array
     */
    protected $aSupportedLocales = [
        'de_DE',
        'en_AU',
        'en_GB',
        'en_US',
        'es_ES',
        'es_XC',
        'fr_FR',
        'fr_XC',
        'it_IT',
        'ja_JP',
        'nl_NL',
        'pl_PL',
        'zh_CN',
        'zh_XC',
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Locale\ResolverInterface      $localeResolver
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->localeResolver = $localeResolver;
        $this->setTemplate($this->sTemplate);
    }

    /**
     * Get shortcut alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param  string $sName
     * @return void
     */
    public function setName($sName)
    {
        $this->name = $sName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getButtonIdent()
    {
        $sButtonIdent = "payone-paypal-button-container";
        if (strpos($this->getName(), "checkout.cart.shortcut.buttons") !== false) {
            $sButtonIdent = "payone-paypal-button-basket";
        } elseif (strpos($this->getName(), "shortcutbuttons") !== false) {
            $sButtonIdent = "payone-paypal-button-minibasket";
        }
        return $sButtonIdent;
    }

    /**
     * Check whether specified locale code is supported. Fallback to en_US
     *
     * @param  string $sLocale
     * @return string
     */
    protected function getSupportedLocaleCode($sLocale = null)
    {
        if (!$sLocale || !in_array($sLocale, $this->aSupportedLocales)) {
            return 'en_US';
        }
        return $sLocale;
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        $sCurrentLocal = $this->localeResolver->getLocale();
        $sPayPalLocal = $this->getSupportedLocaleCode($sCurrentLocal);
        return $sPayPalLocal;
    }
}