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
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/storage',
    'mage/utils/wrapper',
    'Payone_Core/js/action/consumerscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/view/billing-address',
    'Magento_Checkout/js/action/create-billing-address'
], function ($, storage, wrapper, consumerscore, quote, customer, urlBuilder, fullScreenLoader, billing, createBillingAddress) {
    'use strict';

    return function (placeOrderAction) {

        /** Override default place order action and add agreement_ids to request */
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            if (window.checkoutConfig.payment.payone.bonicheckAddressEnabled && window.checkoutConfig.payment.payone.bonicheckIntegrationEvent === 'after_payment') {
                alert('Kabelbruch');

                var serviceUrl;

                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payone-consumerscore', {
                        quoteId: quote.getQuoteId()
                    });
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/payone-consumerscore', {});
                }

                alert('PlaceOrder: ' + serviceUrl);

                var addressData = quote.billingAddress();
                var request = {
                    addressData: addressData,
                    isBillingAddress: true,
                    isVirtual: quote.isVirtual(),
                    dTotal: window.checkoutConfig.quoteData.subtotal
                };

                fullScreenLoader.startLoader();

                $.ajax({
                    url: serviceUrl,
                    type: 'POST',
                    data: JSON.stringify(request),
                    global: true,
                    contentType: 'application/json'
                }).done(
                    function (response) {
                        if (response.success == true) {
                            alert('Bruchstück');
                            if (response.corrected_address != null) {
                                var newBillingAddress = createBillingAddress(response.corrected_address);
                                if (!window.checkoutConfig.payment.payone.addresscheckConfirmCorrection || confirm(response.confirm_message)) {
                                    quote.billingAddress(newBillingAddress);
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

                //return true;
            } else {
                return originalAction(paymentData, messageContainer);
            }
        });
    };
});
