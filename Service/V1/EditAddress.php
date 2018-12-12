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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Service\V1;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InitException;
use Payone\Core\Api\EditAddressInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Web API model for the PAYONE addresscheck
 */
class EditAddress implements EditAddressInterface
{
    /**
     * Factory for the response object
     *
     * @var \Payone\Core\Service\V1\Data\EditAddressResponseFactory
     */
    protected $responseFactory;

    /**
     * Address repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Logger.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Constructor
     *
     * @param \Payone\Core\Service\V1\Data\EditAddressResponseFactory $responseFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface        $addressRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface              $quoteRepository
     * @param Logger                                                  $logger
     * @param QuoteIdMaskFactory                                      $quoteIdMaskFactory
     */
    public function __construct(
        \Payone\Core\Service\V1\Data\EditAddressResponseFactory $responseFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        Logger $logger,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->addressRepository = $addressRepository;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * PAYONE editAddress script
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  mixed $cartId
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @return \Payone\Core\Service\V1\Data\EditAddressResponse
     * @throws InputException
     */
    public function editAddress($cartId, \Magento\Quote\Api\Data\AddressInterface $addressData)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($cartId);
        $quote->setShippingAddress($addressData);
        try {
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Unable to save shipping information. Please check input data.'));
        }

        $oResponse = $this->responseFactory->create();
        $oResponse->setData('success', true);
        return $oResponse;
    }

    /**
     * PAYONE editAddress script
     * The full class-paths must be given here otherwise the Magento 2 WebApi
     * cant handle this with its fake type system!
     *
     * @param  mixed $cartId
     * @param  \Magento\Quote\Api\Data\AddressInterface $addressData
     * @return \Payone\Core\Service\V1\Data\EditAddressResponse
     * @throws InputException
     */
    public function editAddressGuest($cartId, \Magento\Quote\Api\Data\AddressInterface $addressData)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->editAddress($quoteIdMask->getQuoteId(), $addressData);
    }
}
