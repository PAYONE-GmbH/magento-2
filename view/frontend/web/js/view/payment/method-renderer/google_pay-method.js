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
 * @copyright 2003 - 2025 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Payone_Core/js/view/payment/method-renderer/base',
        'jquery',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function (Component, $, checkoutData, quote, additionalValidators, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/google_pay',
                buttonLoaded: false,
                paymentToken: null,
                googlePayButtonId: 'PayoneGooglePayButton',

                // GooglePay fields
                baseRequest: {
                    apiVersion: 2,
                    apiVersionMinor: 0
                },
                paymentsClient: null,
                allowedCardNetworks: ["MASTERCARD", "VISA"],
                allowedCardAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'buttonLoaded',
                        'paymentToken'
                    ]);
                return this;
            },

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            initialize: function () {
                let parentReturn = this._super();
                this.initGooglePayButton();
                return parentReturn;
            },
            removePaymentMethodFromPage: function () {
                document.getElementById(this.getPaymentContainerId()).remove();
            },
            getPaymentContainerId: function () {
                return this.getCode() + '_method_div';
            },
            validate: function () {
                var parentReturn = this._super();
                if (parentReturn === false) {
                    return parentReturn;
                }
                if (this.paymentToken() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('An error occured.')});
                    return false;
                }
                return parentReturn;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.payment_token = this.paymentToken();
                return parentReturn;
            },
            getEnvironment: function () {
                let environment = "PRODUCTION";
                if (this.getFrontendConfigParam('operationMode') == "test") {
                    environment = "TEST";
                }
                return environment;
            },
            selectPrice: function (base_price, price) {
                let returnPrice = base_price;
                if (window.checkoutConfig.payment.payone.currency === "display") {
                    returnPrice = price;
                }
                return returnPrice;
            },
            getSingleLineItem: function (label, base_price, price, type = 'LINE_ITEM') {
                return {
                    "label": label,
                    "type": type,
                    "price": this.formatPrice(this.selectPrice(base_price, price)),
                };
            },
            getLineItems: function () {
                let lineItems = [];

                // Add line items
                let items = quote.getItems();
                if (items && items.length > 0) {
                    for (let item of items) {
                        lineItems.push(this.getSingleLineItem(item.qty + "x " + item.sku + ": " + item.name, item.base_row_total_incl_tax, item.row_total_incl_tax));
                    }
                }

                let totals = quote.totals();
                if (totals) {
                    // Add subtotal
                    lineItems.push(this.getSingleLineItem($t("Subtotal"), totals.base_subtotal, totals.subtotal, 'SUBTOTAL'));

                    // Add Tax
                    lineItems.push(this.getSingleLineItem($t("Estimate Tax"), totals.base_tax_amount, totals.tax_amount, 'TAX'));
                }

                // Add shipping
                let shipping = quote.shippingMethod();
                if (shipping && shipping.amount > 0) {
                    lineItems.push(this.getSingleLineItem($t("Shipping") + shipping.carrier_title + " - " + shipping.method_title, shipping.price_incl_tax, shipping.price_incl_tax)); // no base_price_incl_tax here...
                }

                return lineItems;
            },
            formatPrice: function (price) {
                let returnPrice = parseFloat(price);
                returnPrice.toFixed(2); // Magento prices are formatted like 12.9500 - Google Pay doesn't like that.
                return returnPrice.toString(); // Goole Pay expects prices as string
            },
            startInitButton: function (trys = 0) {
                // Button may not be rendered on first try - try again but not more than 10 times
                if (trys > 10) {
                    return false;
                }

                let elem = document.getElementById(this.getPaymentContainerId());
                if (elem === null) {
                    let self = this;
                    trys++;
                    setTimeout(function() {
                        window.requestAnimationFrame(function() {self.startInitButton(trys);});
                    }, 250);
                } else {
                    this.initButton();
                }
            },
            initButton: function () {
                document.getElementById(this.getPaymentContainerId()).style.display = "";
                this.addGooglePayButton();

                // didn't notice an improvement in performance
                this.prefetchGooglePaymentData();
            },

            /* START GOOGLE PAY JAVASCRIPTS */

            getGooglePaymentsClient: function () {
                if (this.paymentsClient === null) {
                    this.paymentsClient = new google.payments.api.PaymentsClient({environment: this.getEnvironment()});
                }
                return this.paymentsClient;
            },
            getTokenizationSpecification: function () {
                return {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        'gateway': 'payonegmbh',
                        'gatewayMerchantId': this.getFrontendConfigParam('merchantId'),
                    }
                };
            },
            getBaseCardPaymentMethod: function () {
                return {
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: this.allowedCardAuthMethods,
                        allowedCardNetworks: this.allowedCardNetworks
                    }
                };
            },
            getCardPaymentMethod: function () {
                return Object.assign(
                    {},
                    this.getBaseCardPaymentMethod(),
                    {
                        tokenizationSpecification: this.getTokenizationSpecification()
                    }
                );
            },
            getGoogleTransactionInfo: function () {
                let transactionInfo = {
                    countryCode: this.getBillingCountry(),
                    currencyCode: this.getCurrency(),
                    totalPriceStatus: 'FINAL',
                    // set to cart total
                    totalPrice: this.getOrderTotal().toString(),
                };

                let lineItems = this.getLineItems();
                if (lineItems && lineItems.length > 0) {
                    transactionInfo.totalPriceLabel = $t('Order Total');
                    transactionInfo.displayItems = lineItems;
                }
                return transactionInfo;
            },
            getGooglePaymentDataRequest: function () {
                const paymentDataRequest = Object.assign({}, this.baseRequest);
                paymentDataRequest.allowedPaymentMethods = [this.getCardPaymentMethod()];
                paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();
                paymentDataRequest.merchantInfo = {
                    merchantName: this.getFrontendConfigParam('storeName')
                };
                return paymentDataRequest;
            },
            addGooglePayButton: function () {
                let self = this;
                window.onGooglePaymentButtonClicked = window.onGooglePaymentButtonClicked || function () {
                    self.onGooglePaymentButtonClicked();
                };
                const button = this.getGooglePaymentsClient().createButton({
                    onClick: window.onGooglePaymentButtonClicked,
                    allowedPaymentMethods: [this.getBaseCardPaymentMethod()]
                });
                document.getElementById(this.googlePayButtonId).appendChild(button);
            },
            getGoogleIsReadyToPayRequest: function () {
                return Object.assign(
                    {},
                    this.baseRequest,
                    {
                        allowedPaymentMethods: [this.getBaseCardPaymentMethod()]
                    }
                );
            },
            prefetchGooglePaymentData: function () {
                const paymentDataRequest = this.getGooglePaymentDataRequest();
                // transactionInfo must be set but does not affect cache
                paymentDataRequest.transactionInfo = {
                    totalPriceStatus: 'NOT_CURRENTLY_KNOWN', // documentation says "ESTIMATED" or "FINAL" ?!
                    currencyCode: this.getCurrency()
                };

                this.getGooglePaymentsClient().prefetchPaymentData(paymentDataRequest);
            },
            onGooglePayLoaded: function () {
                let self = this;
                this.getGooglePaymentsClient().isReadyToPay(this.getGoogleIsReadyToPayRequest())
                .then(function(response) {
                    if (response.result) {
                        self.startInitButton();
                    } else {
                        self.removePaymentMethodFromPage();
                    }
                })
                .catch(function(err) {
                    // show error in developer console for debugging
                    console.error(err);
                });
            },
            initGooglePayButton: function () {
                if (this.buttonLoaded() === false) {
                    var self = this;
                    $.getScript("https://pay.google.com/gp/p/js/pay.js", function () {
                        self.onGooglePayLoaded();
                    });
                    this.buttonLoaded(true);
                }
            },
            processPayment: function (paymentData) {
                this.paymentToken(paymentData.paymentMethodData.tokenizationData.token);
                this.continueToPayone();
            },
            onGooglePaymentButtonClicked: function () {
                if (additionalValidators.validate()) {
                    const paymentDataRequest = this.getGooglePaymentDataRequest();
                    paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();

                    let self = this;
                    this.getGooglePaymentsClient().loadPaymentData(paymentDataRequest)
                        .then(function(paymentData) {
                            // handle the response
                            self.processPayment(paymentData);
                        })
                        .catch(function(err) {
                            // show error in developer console for debugging
                            console.error(err);
                        });
                }
            }
            /* END GOOGLE PAY JAVASCRIPTS */
        });
    }
);
