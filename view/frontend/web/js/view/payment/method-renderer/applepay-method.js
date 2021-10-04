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
 * @copyright 2003 - 2021 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Payone_Core/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/totals',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (Component, urlBuilder, storage, fullScreenLoader, quote, customer, totals, messageList, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/applepay',
                token: false,
                session: false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'token',
                        'session',
                    ]);
                return this;
            },

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },

            getCurrency: function () {
                if (window.checkoutConfig.payment.payone.currency === "display") {
                    return quote.totals().quote_currency_code;
                }
                return quote.totals().base_currency_code;
            },

            isApplePayAvailable: function () {
                try {
                    return window.ApplePaySession && window.ApplePaySession.canMakePayments();
                } catch (exc) {
                    console.warn('Apple Pay could not be initialized:', exc);
                }
                return false;
            },

            afterPlaceOrder: function () {
                this.session().completePayment({status: 'STATUS_SUCCESS'});
            },

            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.token = JSON.stringify(this.token());
                return parentReturn;
            },

            getOrderTotal: function () {
                if (window.checkoutConfig.payment.payone.currency === "display") {
                    return parseFloat(totals.getSegment('grand_total').value);
                }

                var total = quote.getTotals();
                if (total) {
                    return parseFloat(total()['base_grand_total']);
                }
                return 0;
            },

            getPlaceOrderDeferredObject: function () {
                var self = this;

                return this._super().fail(function () {
                    self.session().abort();
                });
            },

            initializeApplePay: function () {
                var params = {
                    countryCode: quote.billingAddress().countryId,
                    currencyCode: this.getCurrency(),
                    supportedNetworks: window.checkoutConfig.payment.payone.availableApplePayTypes,
                    merchantCapabilities: ['supports3DS'],
                    total: { label: window.checkoutConfig.payment.payone.storeName, amount: this.getOrderTotal() },
                    lineItems: [
                        {label: $t("Order Total"), amount: this.getOrderTotal(), type: 'final'},
                    ],
                };
                var session = new ApplePaySession(3, params);
                var self = this;
                session.onvalidatemerchant = function(event) {
                    var serviceUrl = urlBuilder.createUrl('/carts/mine/payone-getApplePaySession', {});
                    if (!customer.isLoggedIn()) {
                        serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payone-getApplePaySession', {
                            quoteId: quote.getQuoteId()
                        });
                    }

                    return storage.post(
                        serviceUrl
                    ).done(
                        function (response) {
                            if (response.success === true && response.session !== undefined) {
                                session.completeMerchantValidation(JSON.parse(response.session));
                            } else {
                                self.messageContainer.addErrorMessage({'message': "An error occured: " + response.errormessage});
                                session.abort();
                            }
                        }
                    ).fail(
                        function (response) {
                            self.messageContainer.addErrorMessage({'message': $t('An error occured.')});
                            session.abort();
                        }
                    );
                };
                session.onpaymentauthorized = function(event) {
                    self.token(event.payment.token);
                    self.session(session);
                    self.placeOrder();
                };
                session.begin();
            }
        });
    }
);
