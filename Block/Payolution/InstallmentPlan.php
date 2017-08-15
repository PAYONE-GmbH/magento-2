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

namespace Payone\Core\Block\Payolution;

use Magento\Framework\View\Element\Template\Context;

/**
 * Block-class for Payolution InstallmentPlan Ajax-call
 */
class InstallmentPlan extends \Magento\Framework\View\Element\Template
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(Context $context, array $data = []) {
        parent::__construct($context, $data);
        $this->setTemplate('payolution/installment_plan.phtml');
    }

    /**
     * Format price
     *
     * @param double $dPrice
     * @return string
     */
    public function formatPrice($dPrice)
    {
        return number_format($dPrice, 2, ',', '');
    }

    /**
     * Generate installment selection text
     *
     * @param array $aInstallment
     * @return string
     */
    public function getSelectLinkText($aInstallment)
    {
        $sText  = $this->formatPrice($aInstallment['installment']['1']['amount']).' ';
        $sText .= $aInstallment['currency'].' ';
        $sText .= __('per month').' - ';
        $sText .= $aInstallment['duration'].' ';
        $sText .= __('installments');
        return $sText;
    }

    /**
     * Generate payment info text
     *
     * @param string $sKey
     * @param array  $aInstallment
     * @param array  $aPayment
     * @return string
     */
    public function getPaymentInfoText($sKey, $aInstallment, $aPayment)
    {
        $sAmount = $this->formatPrice($aPayment['amount']);
        $sDate = date('d.m.Y', strtotime($aPayment['due']));

        $sText  = $sKey.'. '.__('Installment').': ';
        $sText .= $sAmount.' '.$aInstallment['currency'].' ';
        $sText .= '('.__('due').' '.$sDate.')';
        return $sText;
    }

    /**
     * Return download link for installment draft contract
     *
     * @param int $iInstallments
     * @return string
     */
    public function getDraftDownloadLink($iInstallments)
    {
        return $this->getUrl('payone/payolution/draftDownload/duration/'.$iInstallments);
    }
}
