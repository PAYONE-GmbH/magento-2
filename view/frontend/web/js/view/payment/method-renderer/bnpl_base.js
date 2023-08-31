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
 * PHP version 8
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2022 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'jquery',
        'Payone_Core/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'mage/translate',
    ],
    function ($, Component, quote, customer, $t) {
        'use strict';
        return Component.extend({
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.requestBirthday()) {
                    parentReturn.additional_data.dateofbirth = this.birthyear() + this.birthmonth() + this.birthday();
                }
                if (this.requestTelephone()) {
                    parentReturn.additional_data.telephone = this.telephone();
                }
                return parentReturn;
            },

            initialize: function () {
                let parentReturn = this._super();
                if (this.isChecked() === this.getCode()) {
                    this.selectPaymentMethod();
                }
                return parentReturn;
            },

            getCleanedNumber: function (sDirtyNumber) {
                var sCleanedNumber = '';
                var sTmpChar;
                for (var i = 0; i < sDirtyNumber.length; i++) {
                    sTmpChar = sDirtyNumber.charAt(i);
                    if (sTmpChar != ' ' && (!isNaN(sTmpChar) || /^[A-Za-z]/.test(sTmpChar))) {
                        if (/^[a-z]/.test(sTmpChar)) {
                            sTmpChar = sTmpChar.toUpperCase();
                        }
                        sCleanedNumber = sCleanedNumber + sTmpChar;
                    }
                }
                return sCleanedNumber;
            },

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            requestBirthday: function () {
                if (customer.customerData.dob == undefined || customer.customerData.dob === null) {
                    return true;
                }
                return false;
            },
            requestTelephone: function () {
                if (quote.billingAddress() == null || (typeof quote.billingAddress().telephone != 'undefined' && quote.billingAddress().telephone != '')) {
                    return false;
                }
                return true;
            },
            isAddressDifferent: function () {
                if (window.checkoutConfig.payment.payone.bnpl.differentAddressAllowed[this.getCode()] === true) {
                    return false;
                }
                return (quote.billingAddress() === null || quote.billingAddress().getCacheKey() !== quote.shippingAddress().getCacheKey());
            },
            isB2BOrder: function () {
                if (quote.billingAddress() !== null && typeof quote.billingAddress().company !== undefined && typeof quote.billingAddress().company !== null) {
                    if (quote.billingAddress().company) {
                        return true;
                    }
                }
                return false;
            },
            isPlaceOrderActionAllowedBNPL: function () {
                return this.isAddressDifferent() === false && (this.getCode() === 'payone_bnpl_invoice' || this.isB2BOrder() === false);
            },
            loadJavascriptSnippet: function () {
                if (window.checkoutConfig.payment.payone.bnpl === undefined || window.checkoutConfig.payment.payone.bnpl === false || window.payoneBNPLSnippetLoaded !== undefined || window.payoneBNPLSnippetLoaded === true) {
                    return; // cant load snippet when config isnt filled
                }

                var config = window.checkoutConfig.payment.payone.bnpl;
                var environment = config.environment[this.getCode()];
                var mid = config.mid[this.getCode()];
                var snippetToken = config.payla_partner_id + "_" + mid + "_" + config.uuid;

                $.getScript("https://d.payla.io/dcs/" + config.payla_partner_id + "/" + mid + "/dcs.js")
                .done(function(script, textStatus) {
                    var paylaDcsT = paylaDcs.init(environment, snippetToken);

                    $("head").append("<link>");
                    var css = $("head").children(":last");
                    css.attr({
                        rel:  "stylesheet",
                        type: "text/css",
                        href: "https://d.payla.io/dcs/dcs.css?st=" + snippetToken + "&pi=" + config.payla_partner_id + "&psi=" + mid + "&e=" + environment
                    });
                })
                .fail(function(jqxhr, settings, exception) {
                    console.log("Couldnt load BNPL script");
                });
                window.payoneBNPLSnippetLoaded = true;
            },
            selectPaymentMethod: function () {
                var returnValue = this._super();
                this.loadJavascriptSnippet();
                return returnValue;
            },
            validate: function () {
                if (this.requestBirthday() === true && !this.isDateValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid date.')});
                    return false;
                }
                if (this.requestBirthday() === true && !this.isBirthdayValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('You have to be at least 18 years old to use this payment type!')});
                    return false;
                }
                if (this.requestTelephone() === true && this.telephone() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter your telephone number!')});
                    return false;
                }
                return true;
            }
        });
    }
);
