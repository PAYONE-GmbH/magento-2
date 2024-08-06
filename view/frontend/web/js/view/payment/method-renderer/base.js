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
 * @copyright 2003 - 2016 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/url',
        'mage/translate',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/action/place-order'
    ],
    function (Component, $, additionalValidators, setPaymentInformationAction, url, $t, checkoutData, selectPaymentMethodAction, placeOrderAction) {
        'use strict';
        return Component.extend({
            redirectToPayoneController: function(sUrl) {
                window.location.replace(url.build(sUrl));
            },

            handleRedirectAction: function(sUrl) {
                var self = this;

                this.isPlaceOrderActionAllowed(false);
                this.getPlaceOrderDeferredObject()
                .fail(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                    }
                ).done(
                    function () {
                        self.afterPlaceOrder();
                        self.redirectToPayoneController(sUrl);
                    }
                );
            },

            getPlaceOrderDeferredObject: function () {
                if (window.checkoutConfig.payment.payone.orderDeferredExists === true) {
                    return this._super();
                }
                // fallback for pre 2.1.0 Magentos
                return $.when(
                    placeOrderAction(this.getData(), this.redirectAfterPlaceOrder, this.messageContainer)
                );
            },

            handleSetPaymentInformation: function(sUrl) {
                var self = this;

                this.isPlaceOrderActionAllowed(false);

                $.when(
                    setPaymentInformationAction(this.messageContainer, self.getData())
                ).fail(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                    }
                ).done(
                    function () {
                        self.redirectToPayoneController(sUrl);
                    }
                );
            },

            continueToPayone: function () {
                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction('payone/onepage/redirect/');
                    return false;
                }
            },

            handleDebitPayment: function () {
                if (this.validate() && additionalValidators.validate()) {
                    if (window.checkoutConfig.payment.payone.validateBankCode == true && window.checkoutConfig.payment.payone.bankCodeValidatedAndValid == false) {
                        this.handleBankaccountCheck();
                    } else {
                        this.handleSetPaymentInformation('payone/onepage/debit/');
                        return false;
                    }
                }
            },

            isDateValid: function (iYear, iMonth, iDay) {
                if (!$.isNumeric(iYear) || !$.isNumeric(iMonth) || !$.isNumeric(iDay)) {
                    return false;
                }

                var sBirthDate = iYear + "-" + iMonth + "-" + iDay;
                var oBirthDate = new Date(sBirthDate);

                if (oBirthDate.toString() === 'Invalid Date' || oBirthDate.getFullYear() !== parseInt(iYear) ||
                    oBirthDate.getMonth() + 1 !== parseInt(iMonth) || oBirthDate.getDate() !== parseInt(iDay)) {
                    return false;
                }

                return true;
            },

            isBirthdayValid: function (iYear, iMonth, iDay) {
                var sBirthDate = iYear + "-" + iMonth + "-" + iDay;
                var oBirthDate = new Date(sBirthDate);
                var oMinDate = new Date(new Date().setYear(new Date().getFullYear() - 18));
                if (oBirthDate > oMinDate) {
                    return false;
                }

                return true;
            },
            initialize: function () {
                this._super();
                if(this.getCode() === window.checkoutConfig.payment.payone.canceledPaymentMethod) {
                    selectPaymentMethodAction({method: this.getCode()});
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    if (window.checkoutConfig.payment.payone.isError === true) {
                        this.messageContainer.addErrorMessage({'message': $t('There has been an error processing your payment')});
                    } else {
                        this.messageContainer.addSuccessMessage({'message': $t('Payment has been canceled.')});
                    }
                }
                return this;
            },
            isAgreementVisible: function () {
                if (this.canShowPaymentHintText() || this.canShowAgreementMessage()) {
                    return true;
                }
                return false;
            },
            canShowPaymentHintText: function () {
                return window.checkoutConfig.payment.payone.canShowPaymentHintText;
            },
            getPaymentHintText: function () {
                return window.checkoutConfig.payment.payone.paymentHintText;
            },
            canShowAgreementMessage: function () {
                if (window.checkoutConfig.payment.payone.canShowAgreementMessage && $.inArray(this.getCode(), window.checkoutConfig.payment.payone.consumerScoreEnabledMethods) != -1) {
                    return true;
                }
                return false;
            },
            getAgreementMessage: function () {
                return window.checkoutConfig.payment.payone.agreementMessage;
            },
            getFrontendConfig: function () {
                if (window.checkoutConfig.payment.payone[this.getCode()] !== undefined) {
                    return window.checkoutConfig.payment.payone[this.getCode()];
                }
                return [];
            },
            getFrontendConfigParam: function (param, defaultReturn = '') {
                let frontendConfig = this.getFrontendConfig();
                if (param in frontendConfig) { // check if key exists
                    return frontendConfig[param];
                }
                return defaultReturn;
            }
        });
    }
);
