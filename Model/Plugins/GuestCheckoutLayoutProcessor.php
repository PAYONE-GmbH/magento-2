<?php

namespace Payone\Core\Model\Plugins;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Checkout\Model\Session;
use Payone\Core\Model\Source\CreditratingCheckType;

class GuestCheckoutLayoutProcessor
{
    /**
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $customerMetaData;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $json;

    /**
     * PAYONE order helper
     *
     * @var \Payone\Core\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * GuestCheckoutLayoutProcessor constructor.
     *
     * @param \Magento\Customer\Api\CustomerMetadataInterface $customerMetadata
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Payone\Core\Helper\Checkout $checkoutHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     */
    public function __construct(
        \Magento\Customer\Api\CustomerMetadataInterface $customerMetadata,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Payone\Core\Helper\Checkout $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->json = $json;
        $this->customerMetaData = $customerMetadata;
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->assetRepo = $assetRepo;
    }

    /**
     * Check if form has to be extended
     * Extension needed for guest checkout when Boniversum creditrating is enabled
     *
     * @return bool
     */
    protected function isExtendedCustomerFormNeeded()
    {
        if ($this->checkoutHelper->getCurrentCheckoutMethod($this->checkoutSession->getQuote()) != \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
            return false;
        }

        if (!$this->checkoutHelper->getConfigParam('enabled', 'creditrating', 'payone_protect')) {
            return false;
        }

        $sBonicheckType = $this->checkoutHelper->getConfigParam('type', 'creditrating', 'payone_protect');
        if ($sBonicheckType != CreditratingCheckType::BONIVERSUM_VERITA) {
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array $jsLayout)
    {
        // Only add extra fields to guest checkout
        if ($this->isExtendedCustomerFormNeeded() === false) {
            return $jsLayout;
        }

        $shippingAddress = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];

        $this->addGenderField($shippingAddress);
        $this->addBirthdayField($shippingAddress);

        return $jsLayout;
    }

    /**
     * @param $jsLayout
     * @return void
     */
    private function addGenderField(&$jsLayout)
    {
        $options = $this->getGenderOptions() ?: [];
        $enabled  = $this->isEnabled();
        if (!empty($options) && $enabled) {
            $jsLayout['gender'] = [
                'component' => 'Magento_Ui/js/form/element/select',
                'config' => [
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/select',
                    'id' => 'gender',
                    'options' => $options
                ],
                'label' => __('Gender'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => [
                    'required-entry' => true
                ],
                'sortOrder' => 10,
                'id' => 'gender',
                'dataScope' => 'shippingAddress.gender',
            ];
        }
    }

    /**
     * @param $jsLayout
     * @return void
     */
    private function addBirthdayField(&$jsLayout)
    {
        $enabled  = $this->isEnabled();
        if ($enabled) {
            $jsLayout['dob'] = [
                'component' => 'Magento_Ui/js/form/element/date',
                'config' => [
                    'template' => 'ui/form/field',
                    'additionalClasses' => 'date field-dob',
                    'elementTmpl' => 'ui/form/element/date',
                    'id' => 'dob',
                    'options' => [
                        'changeYear' => true,
                        'changeMonth' => true,
                        'showOn' => "both",
                        'buttonText' => "Select Date",
                        'maxDate' => "-1d",
                        'yearRange' => "-120y:c+nn",
                        'showsTime' => false,
                        'buttonImage' => $this->assetRepo->getUrlWithParams('Magento_Theme::calendar.png', ['_secure' => true])
                    ],
                ],
                'label' => __('Date of Birth'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => [
                    'required-entry' => true
                ],
                'sortOrder' => 1000,
                'id' => 'dob',
                'dataScope' => 'shippingAddress.dob',
            ];
        }
    }

    /**
     * Retrieve customer attribute instance
     *
     * @param string $attributeCode
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface|null
     */
    protected function _getAttribute($attributeCode)
    {
        try {
            return $this->customerMetaData->getAttributeMetadata($attributeCode);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    private function getGenderOptions()
    {
        $optionsData = [];

        $attribute = $this->_getAttribute('gender');
        if ($attribute) {
            $options =  $attribute->getOptions() ?: [];

            foreach ($options as $option) {
                $optionsData[] = [
                    'label' => __($option->getLabel()),
                    'value' => $option->getValue()
                ];
            }
        }
        return $optionsData;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_getAttribute('gender') ? (bool)$this->_getAttribute('gender')->isVisible() : false;
    }
}
