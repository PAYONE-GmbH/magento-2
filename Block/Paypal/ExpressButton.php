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
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Paypal;

use Magento\Framework\View\Element\Template;

/**
 * Block class for the PayPal Express button
 */
class ExpressButton extends Template implements \Magento\Catalog\Block\ShortcutInterface
{
    /**
     * Shortcut alias
     *
     * @var string
     */
    protected $alias = 'payone.block.paypal.expressbutton';

    /**
     * Is mandate link to be shown?
     *
     * @var bool|null
     */
    protected $blShowMandateLink = null;

    /**
     * Instruction notes
     *
     * @var string|bool
     */
    protected $sInstructionNotes = false;

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
        $this->setTemplate('paypal/express_button.phtml');
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
     * URL to paypal start controller
     *
     * @return string
     */
    public function getPayPalExpressLink()
    {
        return $this->getUrl('payone/paypal/express');
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
     * Return URL to PayPal Express logo
     *
     * @return string
     */
    public function getPayPalExpressLogoUrl()
    {
        $sCurrentLocal = $this->localeResolver->getLocale();
        $sPayPalLocal = $this->getSupportedLocaleCode($sCurrentLocal);
        return sprintf('https://www.paypal.com/%s/i/btn/btn_xpressCheckout.gif', $sPayPalLocal);
    }
}
