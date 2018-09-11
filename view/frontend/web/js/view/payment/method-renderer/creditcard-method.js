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
        'jquery',
        'Payone_Core/js/view/payment/method-renderer/base',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer'
    ],
    function ($, Component, messageList, $t, fullScreenLoader, additionalValidators, customer) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/creditcard',
                firstname: '',
                lastname: '',
                pseudocardpan: '',
                saveData: 0,
                showNewData: false
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'firstname',
                        'lastname',
                        'pseudocardpan',
                        'saveData',
                        'showNewData'
                    ]);
                return this;
            },

            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.firstname = this.firstname();
                parentReturn.additional_data.lastname = this.lastname();
                parentReturn.additional_data.pseudocardpan = $('#' + this.getCode() + '_pseudocardpan').val();
                if (typeof window.checkoutConfig.payment.payone.ccCheckResponse !== "undefined") {
                    parentReturn.additional_data.truncatedcardpan = window.checkoutConfig.payment.payone.ccCheckResponse.truncatedcardpan;
                    parentReturn.additional_data.cardtype = window.checkoutConfig.payment.payone.ccCheckResponse.cardtype;
                    parentReturn.additional_data.cardexpiredate = window.checkoutConfig.payment.payone.ccCheckResponse.cardexpiredate;
                }
                if (this.isSaveDataEnabled()) {
                    parentReturn.additional_data.saveData = this.saveData();
                    parentReturn.additional_data.selectedData = this.getSelectedSavedData();
                }
                return parentReturn;
            },

            handleIframes: function () {
                var fieldconfig = window.checkoutConfig.payment.payone.fieldConfig;
                if (typeof fieldconfig.language != 'undefined') {
                    if (fieldconfig.language == 'de') {
                        fieldconfig.language = Payone.ClientApi.Language.de;
                    } else if (fieldconfig.language == 'en') {
                        fieldconfig.language = Payone.ClientApi.Language.en;
                    }
                }

                window.iframes = new Payone.ClientApi.HostedIFrames(fieldconfig, window.checkoutConfig.payment.payone.hostedRequest);
                window.iframes.setCardType("V");

                var sCardTypeId = this.getCode() + '_credit_card_type';
                if (document.getElementById(sCardTypeId)) {
                    document.getElementById(sCardTypeId).onchange = function () {
                        window.iframes.setCardType(this.value); // on change: set new type of credit card to process
                    };
                }
            },
            useSaveDataMode: function () {
                if (this.isSaveDataEnabled() && this.getSavedPaymentData().length > 0) {
                    return true;
                }
                return false;
            },
            isSaveDataEnabled: function () {
                if(!customer.isLoggedIn()) {
                    return false;
                }
                return window.checkoutConfig.payment.payone.saveCCDataEnabled;
            },
            getSavedPaymentData: function () {
                return window.checkoutConfig.payment.payone.savedPaymentData;
            },
            getSelectedSavedData: function () {
                return $('input[name=' + this.getCode() +'_saved_data]:checked').val();
            },
            getSelectedSavedCardExpireData: function () {
                var sSelectedCardPan = this.getSelectedSavedData();
                var aSavedPaymentData = this.getSavedPaymentData();
                for (var i = 0; i < aSavedPaymentData.length; i++) {
                    if (aSavedPaymentData[i].payment_data.cardpan == sSelectedCardPan) {
                        return aSavedPaymentData[i].payment_data.cardexpiredate;
                    }
                }
                return false;
            },
            isSavedPaymentDataUsed: function () {
                var sSelectedSavedData = this.getSelectedSavedData();
                if (this.useSaveDataMode() && sSelectedSavedData && sSelectedSavedData != 'new') {
                    return true;
                }
                return false;
            },
            handleNewDataVisibility: function () {
                var oElem = $('#payone_creditcard_new_data');
                if (oElem.length > 0) {
                    if (oElem[0].checked === true) {
                        $('#payone_creditcard_new_data_container').show();
                    } else {
                        $('#payone_creditcard_new_data_container').hide();
                    }
                }
            },
            showCvc: function () {
                return window.checkoutConfig.payment.payone.checkCvc;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getCreditcardTypes: function () {
                return window.checkoutConfig.payment.payone.availableCardTypes;
            },
            getHostedParam: function (sParam) {
                return window.checkoutConfig.payment.payone.hostedParams[sParam];
            },
            getCcMonths: function () {
                return window.checkoutConfig.payment.ccform.months[this.getCode()];
            },
            getCcYears: function () {
                return window.checkoutConfig.payment.ccform.years[this.getCode()];
            },
            getCcMonthsValues: function () {
                return _.map(this.getCcMonths(), function (value, key) {
                    return {
                        'value': key,
                        'month': value
                    }
                });
            },
            getCcYearsValues: function () {
                return _.map(this.getCcYears(), function (value, key) {
                    return {
                        'value': key,
                        'year': value
                    };
                });
            },

            validate: function () {
                if (this.isSavedPaymentDataUsed() === false) {
                    if (document.getElementById(this.getCode() + '_credit_card_type').value == '') {
                        this.messageContainer.addErrorMessage({'message': $t('Please choose the creditcard type.')});
                        return false;
                    }
                    if (this.firstname() == '') {
                        this.messageContainer.addErrorMessage({'message': $t('Please enter the firstname.')});
                        return false;
                    }
                    if (this.lastname() == '') {
                        this.messageContainer.addErrorMessage({'message': $t('Please enter the lastname.')});
                        return false;
                    }
                } else if (!this.isMinValidityCorrect(this.getSelectedSavedCardExpireData())) {
                    this.messageContainer.addErrorMessage({'message': $t("This transaction could not be performed. Please select another payment method.")});
                    return;
                }
                return true;
            },

            handleCreditcardCheck: function () {
                if (this.isSavedPaymentDataUsed() === false) {
                    // PayOne Request if the data is valid
                    if (window.iframes.isComplete()) {
                        window.ccjs = this;
                        window.processPayoneResponseCCHosted = window.processPayoneResponseCCHosted || function (response) {
                            window.ccjs.processPayoneResponseCCHosted(response);
                        };
                        window.iframes.creditCardCheck('processPayoneResponseCCHosted'); // Perform "CreditCardCheck" to create and get a
                        // PseudoCardPan; then call your function "payCallback"
                        fullScreenLoader.startLoader();
                    } else {
                        this.messageContainer.addErrorMessage({'message': $t("Please enter complete data.")});
                    }
                }
            },
            handleCreditcardPayment: function () {
                var firstValidation = additionalValidators.validate();
                if (!(firstValidation)) {
                    return false;
                }

                if (this.validate() && firstValidation) {
                    if ($('#' + this.getCode() + '_pseudocardpan').val() != '' || this.isSavedPaymentDataUsed()) {
                        this.handleRedirectAction('payone/onepage/redirect/');
                        return false;
                    } else {
                        this.handleCreditcardCheck();
                    }
                }
            },
            isInt: function (value) {
                if (value.length > 0 && !isNaN(value) && parseInt(Number(value)) == value && !isNaN(parseInt(value, 10))) {
                    return true;
                }
                return false;
            },
            isMinValidityCorrect: function (sExpireDate) {
                if (this.isInt(window.checkoutConfig.payment.payone.ccMinValidity)) {
                    var oExpireDate = new Date('20' + sExpireDate.substring(0,2), (parseInt(sExpireDate.substring(2,4))), 1, 0, 0, 0);
                    oExpireDate.setSeconds(oExpireDate.getSeconds() - 1);

                    var oMinValidDate = new Date();
                    oMinValidDate.setDate((parseInt(oMinValidDate.getDate()) + parseInt(window.checkoutConfig.payment.payone.ccMinValidity)));

                    if (oExpireDate < oMinValidDate) {
                        return false;
                    }
                }
                return true;
            },
            processPayoneResponseCCHosted: function (response) {
                fullScreenLoader.stopLoader();
                if (response.status === "VALID") {
                    if (!this.isMinValidityCorrect(response.cardexpiredate)) {
                        this.messageContainer.addErrorMessage({'message': $t("This transaction could not be performed. Please select another payment method.")});
                        return;
                    }
                    if (document.getElementById(this.getCode() + '_pseudocardpan')) {
                        document.getElementById(this.getCode() + '_pseudocardpan').value = response.pseudocardpan;
                    }
                    window.checkoutConfig.payment.payone.ccCheckResponse = response;

                    this.handleRedirectAction('payone/onepage/redirect/');
                } else if (response.status === "INVALID") {
                    this.messageContainer.addErrorMessage({'message': $t(response.errormessage)});
                } else if (response.status === "ERROR") {
                    this.messageContainer.addErrorMessage({'message': $t(response.errormessage)});
                }
            },
            markDefaultSavedPayment: function () {
                if (this.isSaveDataEnabled()) {
                    var savedPayments = this.getSavedPaymentData();
                    for (var i = 0; i < savedPayments.length; i++) {
                        if (savedPayments[i].is_default == 1) {
                            $('#payone_creditcard_new_data_container').hide();
                            $('#payone_creditcard_data_' + savedPayments[i].id).prop("checked", true);
                        }
                    }
                }
            }
        });

    }
);
