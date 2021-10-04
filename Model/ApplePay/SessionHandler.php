<?php

namespace Payone\Core\Model\ApplePay;

use Payone\Core\Model\PayoneConfig;

class SessionHandler
{
    /**
     * Payone shop helper object
     *
     * @var \Payone\Core\Helper\Shop
     */
    protected $shopHelper;

    /**
     * Payone Apple Pay helper object
     *
     * @var \Payone\Core\Helper\ApplePay
     */
    protected $applePayHelper;

    /**
     * Constructor
     *
     * @param \Payone\Core\Helper\Shop     $shopHelper
     * @param \Payone\Core\Helper\ApplePay $applePayHelper
     */
    public function __construct(
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\ApplePay $applePayHelper
    ) {
        $this->shopHelper = $shopHelper;
        $this->applePayHelper = $applePayHelper;
    }

    /**
     * Returns shop domain
     *
     * @return string
     */
    protected function getShopDomain()
    {
        $aUrl = parse_url($this->shopHelper->getStoreBaseUrl());
        if (!empty($aUrl['host'])) {
            return $aUrl['host'];
        }
        return "";
    }

    /**
     * Returns path to certificate file
     *
     * @return string
     * @throws \Exception
     */
    protected function getCertificateFilePath()
    {
        $sCertFile = $this->shopHelper->getConfigParam("certificate_file", PayoneConfig::METHOD_APPLEPAY, "payment");
        $sCertFilePath = $this->applePayHelper->getApplePayUploadPath().$sCertFile;

        if (empty($sCertFile)) {
            throw new \Exception("Certificate file not configured");
        }
        if (!file_exists($sCertFilePath)) {
            throw new \Exception("Certificate file not existing ".$sCertFilePath);
        }
        return $sCertFilePath;
    }

    /**
     * Returns path to private key file
     *
     * @return string
     * @throws \Exception
     */
    protected function getPrivateKeyFilePath()
    {
        $sPrivateKeyFile = $this->shopHelper->getConfigParam("private_key_file", PayoneConfig::METHOD_APPLEPAY, "payment");
        $sPrivateKeyFilePath = $this->applePayHelper->getApplePayUploadPath().$sPrivateKeyFile;

        if (empty($sPrivateKeyFile)) {
            throw new \Exception("Private key file not configured");
        }
        if (!file_exists($sPrivateKeyFilePath)) {
            throw new \Exception("Private key file not existing");
        }
        return $sPrivateKeyFilePath;
    }

    /**
     * Requests apple pay session and returns it
     *
     * @return bool|string
     * @throws \Exception
     */
    public function getApplePaySession()
    {
        $aRequest = [
            'merchantIdentifier' => $this->shopHelper->getConfigParam("merchant_id", PayoneConfig::METHOD_APPLEPAY, "payment"),
            'displayName' => 'PAYONE Apple Pay Magento2',
            'initiative' => 'web',
            'initiativeContext' => $this->getShopDomain(),
        ];

        $ch = curl_init("https://apple-pay-gateway-cert.apple.com/paymentservices/startSession");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aRequest));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->getCertificateFilePath());
        curl_setopt($ch, CURLOPT_SSLKEY, $this->getPrivateKeyFilePath());

        $sKeyPass = $this->shopHelper->getConfigParam("private_key_password", PayoneConfig::METHOD_APPLEPAY, "payment");
        if (!empty($sKeyPass)) {
            curl_setopt($ch, CURLOPT_KEYPASSWD, $sKeyPass);
        }

        $sOutput = curl_exec($ch);

        return $sOutput;
    }
}
