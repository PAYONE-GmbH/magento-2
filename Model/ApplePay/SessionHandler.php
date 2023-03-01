<?php

namespace Payone\Core\Model\ApplePay;

use Payone\Core\Model\PayoneConfig;

class SessionHandler
{
    /**
     * Magento 2 Curl library
     *
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

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
     * Request URL for collecting the Apple Pay session
     *
     * @var string
     */
    protected $applePaySessionUrl =  "https://apple-pay-gateway-cert.apple.com/paymentservices/startSession";

    /**
     * Constructor
     *
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Payone\Core\Helper\Shop            $shopHelper
     * @param \Payone\Core\Helper\ApplePay        $applePayHelper
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\ApplePay $applePayHelper
    ) {
        $this->curl = $curl;
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
        if ($this->applePayHelper->hasCertificateFile() === false) {
            throw new \Exception("Certificate file not configured or missing");
        }
        $sCertFile = $this->shopHelper->getConfigParam("certificate_file", PayoneConfig::METHOD_APPLEPAY, "payment");
        $sCertFilePath = $this->applePayHelper->getApplePayUploadPath().$sCertFile;
        if (!file_exists($sCertFilePath)) {
            $sCertFilePath = $this->applePayHelper->getApplePayUploadPath(true).$sCertFile;
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
        if ($this->applePayHelper->hasPrivateKeyFile() === false) {
            throw new \Exception("Private key file not configured or missing");
        }
        $sPrivateKeyFile = $this->shopHelper->getConfigParam("private_key_file", PayoneConfig::METHOD_APPLEPAY, "payment");
        $sPrivateKeyFilePath = $this->applePayHelper->getApplePayUploadPath().$sPrivateKeyFile;
        if (!file_exists($sPrivateKeyFilePath)) {
            $sPrivateKeyFilePath = $this->applePayHelper->getApplePayUploadPath(true).$sPrivateKeyFile;
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

        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curl->setOption(CURLOPT_SSLCERT, $this->getCertificateFilePath());
        $this->curl->setOption(CURLOPT_SSLKEY, $this->getPrivateKeyFilePath());

        $sKeyPass = $this->shopHelper->getConfigParam("private_key_password", PayoneConfig::METHOD_APPLEPAY, "payment");
        if (!empty($sKeyPass)) {
            $this->curl->setOption( CURLOPT_KEYPASSWD, $sKeyPass);
        }

        $this->curl->post($this->applePaySessionUrl, json_encode($aRequest));
        return $this->curl->getBody();
    }
}
