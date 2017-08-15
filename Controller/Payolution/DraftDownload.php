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

namespace Payone\Core\Controller\Payolution;

use Magento\Sales\Model\Order;
use Payone\Core\Model\PayoneConfig;

/**
 * Controller for mandate download
 */
class DraftDownload extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * PAYONE payment helper
     *
     * @var \Payone\Core\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Result factory for file-download
     *
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Order object
     *
     * @var Order
     */
    protected $oOrder = null;

    /**
     * Magento curl object
     *
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Magento\Checkout\Model\Session                 $checkoutSession
     * @param \Payone\Core\Helper\Payment                     $paymentHelper
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\HTTP\Client\Curl             $curl
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Payone\Core\Helper\Payment $paymentHelper,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->resultRawFactory = $resultRawFactory;
        $this->curl = $curl;
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
    }

    /**
     * Get pdf-file string
     *
     * @return string
     */
    protected function getInstallmentContractDraft()
    {
        $iDuration = $this->getRequest()->getParam('duration');
        $aDraftLinks = $this->checkoutSession->getInstallmentDraftLinks();
        $sUser = $this->paymentHelper->getConfigParam('installment_draft_user', PayoneConfig::METHOD_PAYOLUTION_INSTALLMENT, 'payone_payment');
        $sPassword = $this->paymentHelper->getConfigParam('installment_draft_password', PayoneConfig::METHOD_PAYOLUTION_INSTALLMENT, 'payone_payment');
        if (isset($aDraftLinks[$iDuration]) && $sUser && $sPassword) {
            $sDownloadUrl = str_ireplace('https://', 'https://'.$sUser.':'.$sPassword.'@', $aDraftLinks[$iDuration]);
            $this->curl->get($sDownloadUrl);
            return $this->curl->getBody();
        }
        return false;
    }

    /**
     * Output mandate as pdf download
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $oResultRaw = $this->resultRawFactory->create();
        $sFilename = __('terms-of-payment').'.pdf';

        $sContractDraft = $this->getInstallmentContractDraft();
        if ($sContractDraft !== false) {
            $oResultRaw->setHeader("Content-Type", "application/pdf", true);
            $oResultRaw->setHeader("Content-Disposition", 'attachment; filename="'.$sFilename.'"', true);
            $oResultRaw->setContents($sContractDraft);
        } else {
            $oResultRaw->setContents(__('An unknown error occurred'));
        }
        return $oResultRaw;
    }
}
