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
     * Payment helper object
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Pattern for Apple Pay gateway hosts the validation URL may point to
     * Matches e.g. apple-pay-gateway.apple.com, apple-pay-gateway-cert.apple.com,
     * apple-pay-gateway-nc-pod1.apple.com and cn-apple-pay-gateway-sh-pod1.apple.com
     *
     * @var string
     */
    private const ALLOWED_HOST_PATTERN = '/^(cn-)?apple-pay-gateway(-[a-z0-9]+)*\.apple\.com$/';

    /**
     * Constructor
     *
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Payone\Core\Helper\Shop            $shopHelper
     * @param \Payone\Core\Helper\ApplePay        $applePayHelper
     * @param \Magento\Payment\Helper\Data        $dataHelper
     * @param \Magento\Checkout\Model\Session     $checkoutSession
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\ApplePay $applePayHelper,
        \Magento\Payment\Helper\Data $dataHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->curl = $curl;
        $this->shopHelper = $shopHelper;
        $this->applePayHelper = $applePayHelper;
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSession;
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
     * Checks if the given file is PEM encoded
     *
     * @param  string $sFilePath
     * @return bool
     */
    protected function isPemFile($sFilePath)
    {
        if (!is_file($sFilePath) || !is_readable($sFilePath)) {
            return false;
        }
        $sContent = file_get_contents($sFilePath);
        return $sContent !== false && strpos($sContent, '-----BEGIN') !== false;
    }

    /**
     * Requests the Apple Pay session directly from Apple with the merchants own certificate
     *
     * @param  string $validationUrl
     * @return bool|string
     * @throws \Exception
     */
    protected function getApplePaySessionThroughDevAccount($validationUrl)
    {
        $aRequest = [
            'merchantIdentifier' => $this->shopHelper->getConfigParam("merchant_id", PayoneConfig::METHOD_APPLEPAY, "payment"),
            'displayName' => 'PAYONE Apple Pay Magento2',
            'initiative' => 'web',
            'initiativeContext' => $this->getShopDomain(),
        ];

        $sCertFilePath = $this->getCertificateFilePath();

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curl->setOption(CURLOPT_SSLCERT, $sCertFilePath);
        if ($this->isPemFile($sCertFilePath) === false) {
            // The merchant_id.cer downloaded from the Apple developer portal is DER encoded
            $this->curl->setOption(CURLOPT_SSLCERTTYPE, 'DER');
        }
        $this->curl->setOption(CURLOPT_SSLKEY, $this->getPrivateKeyFilePath());

        $sKeyPass = $this->shopHelper->getConfigParam("private_key_password", PayoneConfig::METHOD_APPLEPAY, "payment");
        if (!empty($sKeyPass)) {
            $this->curl->setOption( CURLOPT_KEYPASSWD, $sKeyPass);
        }

        $this->curl->post($validationUrl, json_encode($aRequest));
        return $this->curl->getBody();
    }

    /**
     * Requests apple pay session and returns it
     *
     * @param string $validationUrl
     * @return bool|string
     * @throws \Exception
     */
    public function getApplePaySession($validationUrl)
    {
        $scheme = parse_url($validationUrl, PHP_URL_SCHEME);
        $host = strtolower((string)parse_url($validationUrl, PHP_URL_HOST));
        if ($scheme !== 'https' || preg_match(self::ALLOWED_HOST_PATTERN, $host) !== 1) {
            throw new \Exception('Disallowed Apple Pay validation URL');
        }

        return $this->getApplePaySessionThroughDevAccount($validationUrl);
    }
}
