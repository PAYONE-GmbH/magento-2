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
 * @copyright 2003 - 2024 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Payone_Core/js/view/payment/method-renderer/base',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/full-screen-loader',
        'Payone_Core/js/action/loadamazonpayapbsession',
        'mage/translate'
    ],
    function (Component, $, quote, checkoutData, totals, fullScreenLoader, loadAmazonPayApbSession, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/amazonpayv2',
                telephone: '',
                buttonLoaded: false
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'telephone',
                        'buttonLoaded'
                    ]);
                return this;
            },
            validate: function () {
                if (this.requestTelephone() === true && this.telephone() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter your telephone number!')});
                    return false;
                }
                return true;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.requestTelephone()) {
                    parentReturn.additional_data.telephone = this.telephone();
                }
                return parentReturn;
            },

            getCurrency: function () {
                if (window.checkoutConfig.payment.payone.currency === "display") {
                    return quote.totals().quote_currency_code;
                }
                return quote.totals().base_currency_code;
            },

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            initialize: function () {
                let parentReturn = this._super();
                if (checkoutData.getSelectedPaymentMethod() === this.getCode()) {
                    this.initAmazonPayButton();
                }
                return parentReturn;
            },
            requestTelephone: function () {
                if (quote.billingAddress() == null || (typeof quote.billingAddress().telephone != 'undefined' && quote.billingAddress().telephone != '')) {
                    return false;
                }
                return true;
            },
            selectPaymentMethod: function () {
                this.initAmazonPayButton();
                return this._super();
            },
            initAmazonPayButton: function () {
                if (this.buttonLoaded() === false) {
                    var amazonPayMethod = this;
                    $.getScript("https://static-eu.payments-amazon.com/checkout.js", function () {
                        amazonPayMethod.renderAmazonPayButton();
                    });
                    this.buttonLoaded(true);
                }
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
            getProductType: function () {
                if (quote.isVirtual()) {
                    return 'PayOnly';
                }
                return 'PayAndShip';
            },
            renderAmazonPayButton: function () {
                var self = this;

                let buttonConfig = {
                    // set checkout environment
                    merchantId: this.getFrontendConfigParam('merchantId'),
                    publicKeyId: this.getFrontendConfigParam('buttonPublicKey'),
                    ledgerCurrency: this.getCurrency(),
                    productType: this.getProductType(),
                    placement: 'Checkout',
                    buttonColor: this.getFrontendConfigParam('buttonColor')
                };
                if (this.getFrontendConfigParam('buttonLanguage') != 'none') {
                    buttonConfig.checkoutLanguage = this.getFrontendConfigParam('buttonLanguage');
                }
                if (this.getFrontendConfigParam('useSandbox') == 1) {
                    buttonConfig.sandbox = true;
                }

                let amazonPayButton = amazon.Pay.renderButton('#AmazonPayAPB', buttonConfig);
                amazonPayButton.onClick(function(){
                    self.isPlaceOrderActionAllowed(false);
                    self.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function (orderId) {
                            self.afterPlaceOrder();

                            let ajaxCall = loadAmazonPayApbSession(orderId);
                            ajaxCall.done(
                                function (response) {
                                    fullScreenLoader.stopLoader();
                                    if (response && response.success === true) {
                                        amazonPayButton.initCheckout({
                                            estimatedOrderAmount: { "amount": self.getOrderTotal(), "currencyCode": self.getCurrency()},
                                            createCheckoutSessionConfig: {
                                                payloadJSON: response.payload,
                                                signature: response.signature,
                                                publicKeyId: self.getFrontendConfigParam('buttonPublicKey')
                                            }
                                        });
                                    } else if (response && response.success === false) {
                                        alert('An error occured.');
                                    }
                                }
                            ).fail(
                                function (response) {
                                    fullScreenLoader.stopLoader();
                                    //errorProcessor.process(response, messageContainer);
                                    alert('An error occured.');
                                }
                            );
                        }
                    );
                });
            }
        });
    }
);
