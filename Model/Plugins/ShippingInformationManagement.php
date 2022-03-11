<?php

namespace Payone\Core\Model\Plugins;

use Magento\Checkout\Model\ShippingInformationManagement as ShippingInformationManagementOrig;
use Magento\Checkout\Api\Data\ShippingInformationInterface;

/**
 * Plugin for Magentos ShippingInformationManagement class
 */
class ShippingInformationManagement
{
    /**
     * Checkout session model
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(\Magento\Checkout\Model\Session $checkoutSession) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Writes extensionAttributes to session for later use in Consumerscore
     *
     * @param ShippingInformationManagementOrig $oSource
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return null
     */
    public function beforeSaveAddressInformation(ShippingInformationManagementOrig $oSource, $cartId, ShippingInformationInterface $addressInformation)
    {
        $address = $addressInformation->getShippingAddress();
        $extensionAttributes = $address->getExtensionAttributes();
        if (!empty($extensionAttributes->getGender())) {
            $this->checkoutSession->setPayoneGuestGender($extensionAttributes->getGender());
        }
        if (!empty($extensionAttributes->getDateofbirth())) {
            $this->checkoutSession->setPayoneGuestDateofbirth($extensionAttributes->getDateofbirth());
        }
        return null;
    }
}
