<?php

namespace Payone\Core\Api\Data;

interface AddresscheckResponseInterface
{
    /**
     * Returns the shipping carrier title.
     *
     * @return bool
     */
    public function getSuccess();

    /**
     * Returns the corrected address
     *
     * @return \Magento\Quote\Api\Data\AddressInterface
     */
    public function getCorrectedAddress();

    /**
     * Returns errormessage
     *
     * @return string
     */
    public function getErrormessage();

    /**
     * Return confirm message
     *
     * @return string
     */
    public function getConfirmMessage();
}
