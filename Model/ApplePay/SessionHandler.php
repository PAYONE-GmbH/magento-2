<?php

namespace Payone\Core\Model\ApplePay;

use Payone\Core\Model\PayoneConfig;

class SessionHandler
{
    /**
     * Factory for Magento 2 Curl library
     *
     * @var \Magento\Framework\HTTP\Client\CurlFactory
     */
    protected $curlFactory;

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
     * Apple Pay production gateway, used when the frontend does not send a validation URL
     *
     * @var string
     */
    const DEFAULT_VALIDATION_URL = 'https://apple-pay-gateway.apple.com/paymentservices/startSession';

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
     * @param \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
     * @param \Payone\Core\Helper\Shop                   $shopHelper
     * @param \Payone\Core\Helper\ApplePay               $applePayHelper
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\CurlFactory $curlFactory,
        \Payone\Core\Helper\Shop $shopHelper,
        \Payone\Core\Helper\ApplePay $applePayHelper
    ) {
        $this->curlFactory = $curlFactory;
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
     * Requests the Apple Pay merchant session from Apple with the merchants certificate
     *
     * @param  string $sValidationUrl
     * @return bool|string
     * @throws \Exception
     */
    protected function requestMerchantSession($sValidationUrl)
    {
        $aRequest = [
            'merchantIdentifier' => $this->applePayHelper->getMerchantId(),
            'displayName' => 'PAYONE Apple Pay Magento2',
            'initiative' => 'web',
            'initiativeContext' => $this->getShopDomain(),
        ];

        $sCertFilePath = $this->getCertificateFilePath();

        $oCurl = $this->curlFactory->create();
        $this->shopHelper->setCurlSslOptions($oCurl);
        $oCurl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $oCurl->setOption(CURLOPT_SSLCERT, $sCertFilePath);
        if ($this->isPemFile($sCertFilePath) === false) {
            // The merchant_id.cer downloaded from the Apple developer portal is DER encoded
            $oCurl->setOption(CURLOPT_SSLCERTTYPE, 'DER');
        }
        $oCurl->setOption(CURLOPT_SSLKEY, $this->getPrivateKeyFilePath());

        $sKeyPass = $this->shopHelper->getConfigParam("private_key_password", PayoneConfig::METHOD_APPLEPAY, "payment");
        if (!empty($sKeyPass)) {
            $oCurl->setOption( CURLOPT_KEYPASSWD, $sKeyPass);
        }

        $oCurl->post($sValidationUrl, json_encode($aRequest));
        return $oCurl->getBody();
    }

    /**
     * Requests apple pay session and returns it
     * Falls back to the Apple Pay production gateway when no validation URL is given
     *
     * @param  string|null $sValidationUrl
     * @return bool|string
     * @throws \Exception
     */
    public function getApplePaySession($sValidationUrl = null)
    {
        if (empty($sValidationUrl)) {
            $sValidationUrl = self::DEFAULT_VALIDATION_URL;
        }

        $sScheme = parse_url($sValidationUrl, PHP_URL_SCHEME);
        $sHost = strtolower((string)parse_url($sValidationUrl, PHP_URL_HOST));
        if ($sScheme !== 'https' || preg_match(self::ALLOWED_HOST_PATTERN, $sHost) !== 1) {
            throw new \Exception('Disallowed Apple Pay validation URL');
        }

        return $this->requestMerchantSession($sValidationUrl);
    }
}
