<?php
namespace Payone\Core\Api;

interface AddresscheckInterface
{
    /**
     * PAYONE addresscheck
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @param  bool $isBillingAddress
     * @param  bool $isVirtual
     * @param  double $dTotal
     * @return \Payone\Core\Service\V1\Data\AddresscheckResponse
     */
    public function checkAddress(\Magento\Quote\Api\Data\AddressInterface $addressData, $isBillingAddress, $isVirtual, $dTotal);
}
