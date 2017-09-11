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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Payone_Core/js/view/payment/method-renderer/base',
        'jquery',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'Payone_Core/js/action/installmentplan',
        'Magento_Checkout/js/model/payment/additional-validators',
        'prototype'
    ],
    function (Component, $, $t, quote, urlBuilder, installmentplan, additionalValidators) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/payolution_installment',
                birthday: '',
                birthmonth: '',
                birthyear: '',
                tradeRegistryNumber: '',
                iban: '',
                bic: '',
                agreement: false
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'birthday',
                        'birthmonth',
                        'birthyear',
                        'tradeRegistryNumber',
                        'iban',
                        'bic',
                        'agreement'
                    ]);
                return this;
            },
            getData: function () {
                document.getElementById(this.getCode() + '_iban').value = this.getCleanedNumber(this.iban());
                document.getElementById(this.getCode() + '_bic').value = this.getCleanedNumber(this.bic());

                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.requestBirthday()) {
                    parentReturn.additional_data.dateofbirth = this.birthyear() + this.birthmonth() + this.birthday();
                }
                if (this.isB2bMode()) {
                    parentReturn.additional_data.trade_registry_number = this.tradeRegistryNumber();
                    parentReturn.additional_data.b2bmode = true;
                }
                parentReturn.additional_data.iban = this.getCleanedNumber(this.iban());
                parentReturn.additional_data.bic = this.getCleanedNumber(this.bic());
                parentReturn.additional_data.duration = $('#' + this.getCode() + '_duration').val();
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
            displayPayolutionOverlay: function () {
                $('#' + this.getCode() + '_overlay').show();
            },
            removePayolutionOverlay: function () {
                $('#' + this.getCode() + '_overlay').hide();
            },
            getPrivacyDeclaration: function () {
                return window.checkoutConfig.payment.payone.payolution.privacyDeclaration.invoice;
            },
            isB2bMode: function () {
                if (window.checkoutConfig.payment.payone.payolution.b2bMode.invoice == true &&
                    quote.billingAddress() != null &&
                    typeof quote.billingAddress().company != 'undefined' &&
                    quote.billingAddress().company != ''
                ) {
                    return true;
                }
                return false;
            },
            requestBirthday: function () {
                return !this.isB2bMode();
            },
            validate: function () {
                if (this.requestBirthday() == true && !this.isBirthdayValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('You have to be at least 18 years old to use this payment type!')});
                    return false;
                }
                if (this.agreement() == false) {
                    this.messageContainer.addErrorMessage({'message': $t('Please confirm the transmission of the necessary data to payolution!')});
                    return false;
                }
                if ($('#' + this.getCode() + '_installmentplan').html() != '' && $('#' + this.getCode() + '_duration').val() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please select your desired number of installments')});
                    return false;
                }
                if ($('#' + this.getCode() + '_duration').val() != '' && this.iban() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid IBAN.')});
                    return false;
                }
                if ($('#' + this.getCode() + '_duration').val() != '' &&this.bic() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid BIC.')});
                    return false;
                }
                return true;
            },
            handleInstallment: function () {
                if (this.validate() && additionalValidators.validate()) {
                    window.payolution_installment = this;
                    window.switchInstallmentPlan = window.switchInstallmentPlan || function (sKey, sCode, iInstallments) {
                            window.payolution_installment.switchInstallmentPlan(sKey, sCode, iInstallments);
                        }
                    installmentplan(this, '19601212');
                }
            },
            switchInstallmentPlan: function (sKey, sCode, iInstallments) {
                $$('.payolution_installmentplans').each(
                    function (e) {
                        e.hide();
                    }
                );
                $$('.payolution_installment_overview').each(
                    function (e) {
                        e.hide();
                    }
                );

                $('#payolution_installmentplan_' + sKey).show();
                $('#payolution_installment_overview_' + sKey).show();
                $('#' + sCode + '_duration').val(iInstallments);
            }
        });
    }
);
