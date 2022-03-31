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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Payone_Core/js/view/payment/method-renderer/base',
        'jquery',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Payone_Core/js/action/start-klarna-widget',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/method-list',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'mage/translate'
    ],
    function (Component, $, urlBuilder, storage, fullScreenLoader, quote, customer, startKlarnaWidget, messageList, methodList, checkoutData, selectPaymentMethodAction, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/klarna'
            },
            authToken: false,
            methodCategories: {
                payone_klarna_invoice: 'pay_later',
                payone_klarna_debit: 'direct_debit',
                payone_klarna_installment: 'pay_over_time',
            },

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },

            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.authToken !== false && $('#klarna_subtype').val() != '') {
                    parentReturn.method = $('#klarna_subtype').val();
                }
                parentReturn.additional_data.authorization_token = this.authToken;
                parentReturn.additional_data.klarna_subtype = $('#klarna_subtype').val();
                return parentReturn;
            },

            selectPaymentMethod: function () {
                var data = this.getData();
                data.method = this.item.method;
                selectPaymentMethodAction(data);
                checkoutData.setSelectedPaymentMethod(this.item.method);

                return true;
            },

            isKlarnaMethodActive: function (methodCode) {
                var returnVal = false;
                $.each(methodList(), function( key, value ) {
                    if (value.method == methodCode) {
                        returnVal = true;
                        $('#klarna_method_selection').show();
                    }
                });
                return returnVal;
            },

            getKlarnaMethodTitle: function (methodCode) {
                return window.checkoutConfig.payment.payone.klarnaTitles[methodCode];
            },

            getCustomerEmail: function() {
                var email = customer.customerData.email;
                if (!customer.isLoggedIn()) {
                    email = quote.guestEmail;
                }
                return email;
            },

            startKlarnaCheckout: function (event, elem) {
                var methodeCode = elem.currentTarget.id.replace("_selection", "");
                var paymentMethodCategory = this.methodCategories[methodeCode];

                var serviceUrl;

                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payone-startKlarna', {
                        quoteId: quote.getQuoteId()
                    });
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/payone-startKlarna', {});
                }

                var request = {
                    cartId: quote.getQuoteId(),
                    paymentCode: methodeCode,
                    shippingCosts: this.getShippingCosts(),
                    customerEmail: this.getCustomerEmail()
                };
                var self = this;

                fullScreenLoader.startLoader();

                $('#klarnaInvoiceWidgetContainer').html("");
                $('#klarna_subtype').val(methodeCode);

                return storage.post(
                    serviceUrl,
                    JSON.stringify(request)
                ).done(
                    function (response) {
                        if (response.success == true) {
                            startKlarnaWidget(response.client_token, self, methodeCode, paymentMethodCategory, 'klarnaInvoiceWidgetContainer');
                        } else {
                            self.messageContainer.addErrorMessage({'message': response.errormessage});
                        }
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        self.messageContainer.addErrorMessage({'message': $t('An error occured.')});
                        fullScreenLoader.stopLoader();
                    }
                );
            },

            getShippingCosts: function () {
                var totals = quote.totals();
                if (window.checkoutConfig.payment.payone.currency === "display") {
                    return totals.shipping_incl_tax;
                }
                return totals.base_shipping_incl_tax;
            },

            getCurrency: function (totals) {
                if (window.checkoutConfig.payment.payone.currency === "display") {
                    return totals.quote_currency_code;
                }
                return totals.base_currency_code;
            },

            validate: function () {
                var self = this;

                var billingAddress = quote.billingAddress();
                var shippingAddress = quote.shippingAddress();
                var totals = quote.totals();

                var data = {
                    purchase_country: billingAddress.countryId,
                    purchase_currency: this.getCurrency(totals),
                    locale: window.checkoutConfig.payment.payone.fullLocale.replace('_', '-'),
                    billing_address: {
                        given_name: billingAddress.firstname,
                        family_name: billingAddress.lastname,
                        email: this.getCustomerEmail(),
                        street_address: billingAddress.street[0],
                        //street_address2: "2. Stock",
                        postal_code: billingAddress.postcode,
                        city: billingAddress.city,
                        region: billingAddress.regionCode,
                        phone: billingAddress.telephone,
                        country: billingAddress.countryId
                    },
                    shipping_address: {
                        given_name: shippingAddress.firstname,
                        family_name: shippingAddress.lastname,
                        email: this.getCustomerEmail(),
                        street_address: shippingAddress.street[0],
                        postal_code: shippingAddress.postcode,
                        city: shippingAddress.city,
                        region: shippingAddress.regionCode,
                        phone: shippingAddress.telephone,
                        country: shippingAddress.countryId
                    }
                };

                if (billingAddress.company !== undefined) {
                    data.billing_address.organization_name = billingAddress.company;
                    data.customer = {organization_registration_id: ''};
                }
                if (shippingAddress.company !== undefined) {
                    data.shipping_address.organization_name = shippingAddress.company;
                    data.customer = {organization_registration_id: ''};
                }

                if (self.authToken === false) {
                    Klarna.Payments.authorize({
                        payment_method_category: this.methodCategories[$('#klarna_subtype').val()]
                    }, data, function(res) {
                        if (res.approved === true) {
                            self.authToken = res.authorization_token;
                            self.continueToPayone();
                        } else if (res.approved === false) {
                            if (res.show_form === false) {
                                self.messageContainer.addErrorMessage({'message': $t('Klarna payment can not be offered for this order.')});
                                $('#' + self.getCode() + '_check').prop( "disabled", true );
                                $('#klarnaInvoiceWidgetContainer').hide();
                            } else {
                                self.messageContainer.addErrorMessage({'message': $t('Klarna authorization was not approved.')});
                            }
                        }
                    });
                }

                if (self.authToken !== false) {
                    return true;
                }
                return false;
            }
        });
    }
);
