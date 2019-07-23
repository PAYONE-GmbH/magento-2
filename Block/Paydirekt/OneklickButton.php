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
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Block\Paydirekt;

use Magento\Framework\View\Element\Template;

/**
 * Block class for the Paydirekt Oneklick button
 */
class OneklickButton extends Template implements \Magento\Catalog\Block\ShortcutInterface
{
    /**
     * Shortcut alias
     *
     * @var string
     */
    protected $alias = 'payone.block.paydirekt.oneklick';

    /**
     * Asset repository object
     *
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\View\Asset\Repository         $assetRepo
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->assetRepo = $assetRepo;
        $this->setTemplate('paydirekt/oneklick_button.phtml');
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
    public function getReviewLink()
    {
        return $this->getUrl('payone/paydirekt/agreement');
    }

    /**
     * Return URL to Paydirekt oneKlick image
     *
     * @return string
     */
    public function getLogoUrl()
    {
        $params = [
            'theme' => 'Magento/luma',
            'area' => 'frontend',
        ];
        $asset = $this->_assetRepo->createAsset('Payone_Core::images/paydirekt.png', $params);
        return $asset->getUrl();
    }
}
