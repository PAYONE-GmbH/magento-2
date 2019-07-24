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
define([
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Payone_Core/js/action/edit-address',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/view/shipping',
        'mage/url'
    ], function ($, quote, customer, urlBuilder, fullScreenLoader, createBillingAddress, createShippingAddress, selectShippingAddress, editAddress, checkoutData, shippingView, buildUrl) {
        'use strict';
        return {
            handleCreditrating: function() {
                var serviceUrl;

                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payone-consumerscore', {
                        quoteId: quote.getQuoteId()
                    });
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/payone-consumerscore', {});
                }

                var addressData = quote.billingAddress();
                var request = {
                    addressData: addressData,
                    isBillingAddress: true,
                    isVirtual: quote.isVirtual(),
                    dTotal: window.checkoutConfig.quoteData.subtotal,
                    sIntegrationEvent: 'after_payment'
                };

                fullScreenLoader.startLoader();

                var self = this;

                $.ajax({
                    url: buildUrl.build(serviceUrl),
                    type: 'POST',
                    data: JSON.stringify(request),
                    global: true,
                    contentType: 'application/json',
                    async: false
                }).done(
                    function (response) {
                        if (response.success == true) {
                            if (response.corrected_address != null) {
                                var sameAddress = false;
                                if (self.isAddressTheSame(quote.billingAddress(), quote.shippingAddress())) {
                                    sameAddress = true;
                                }

                                if (!window.checkoutConfig.payment.payone.addresscheckConfirmCorrection || confirm(response.confirm_message)) {
                                    quote.billingAddress(createBillingAddress(response.corrected_address));
                                    if (sameAddress === true) {
                                        self.payoneUpdateAddressRegistered(response.corrected_address);
                                    }
                                }
                            }
                        } else {
                            alert(response.errormessage);
                        }
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        //errorProcessor.process(response, messageContainer);
                        alert('An error occured.');
                        fullScreenLoader.stopLoader();
                    }
                );
            },
            isAddressTheSame: function(billing, shipping) {
                if (this.getAddressInline(billing) == this.getAddressInline(shipping)) {
                    return true;
                }
                return false;
            },
            getAddressInline: function(address) {
                return address.firstname + address.lastname + address.street.join("") + address.postcode + address.city + address.countryId;
            },
            payoneReplaceInSelectedShippingAddress: function (sReplace, sReplaceWith) {
                var oElem = $('.shipping-address-item.selected-item');
                if (oElem.length > 0) {
                    oElem.html(oElem.html().replace(sReplace, sReplaceWith));
                }
            },
            payoneUpdateField: function(oSourceAddress, oResponseAddress, sField) {
                if (sField === "street") {
                    if (oSourceAddress[sField][0] !== oResponseAddress[sField][0]) {
                        this.payoneReplaceInSelectedShippingAddress(oSourceAddress[sField][0], oResponseAddress[sField][0]);
                        oSourceAddress[sField][0] = oResponseAddress[sField][0];
                    }
                } else {
                    if (oSourceAddress[sField] !== oResponseAddress[sField]) {
                        this.payoneReplaceInSelectedShippingAddress(oSourceAddress[sField], oResponseAddress[sField]);
                        oSourceAddress[sField] = oResponseAddress[sField];
                    }
                }
                return oSourceAddress;
            },
            payoneUpdateAddress: function (addressData) {
                this.payoneUpdateAddressRegistered(addressData);
            },
            payoneUpdateAddressRegistered: function (addressData) {
                var newShippingAddress = quote.shippingAddress();
                var aUpdateFields = ["postcode", "firstname", "lastname", "street", "city"];
                for (var i = 0; i < aUpdateFields.length; i++) {
                    newShippingAddress = this.payoneUpdateField(newShippingAddress, addressData, aUpdateFields[i]);
                }

                editAddress(addressData, false);

                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
            },
            validate: function() {
                if (window.checkoutConfig.payment.payone.bonicheckAddressEnabled && window.checkoutConfig.payment.payone.bonicheckIntegrationEvent === 'after_payment') {
                    this.handleCreditrating();
                }
                return true;
            }
        }
    }
);
