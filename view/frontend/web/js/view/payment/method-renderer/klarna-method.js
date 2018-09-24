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
        'Payone_Core/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/quote',
        'mage/translate'
    ],
    function (Component, quote, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/klarna',
                telephone: '',
                addinfo: '',
                delAddinfo: '',
                gender: '',
                personalId: '',
                birthday: '',
                birthmonth: '',
                birthyear: '',
                agreement: false
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'telephone',
                        'addinfo',
                        'delAddinfo',
                        'gender',
                        'personalId',
                        'birthday',
                        'birthmonth',
                        'birthyear',
                        'agreement'
                    ]);
                return this;
            },
            requestTelephone: function () {
                if (this.getBillingCountry() !== false && this.getBillingCountry() !== '') {
                    return false;
                }
                return true;
            },
            requestAddressAddInfo: function () {
                if (this.getBillingCountry() !== false && this.getBillingCountry() !== 'NL') {
                    return false;
                }
                return true;
            },
            requestDelAddressAddInfo: function () {
                if (this.getBillingCountry() !== false && this.getBillingCountry() !== 'NL') {
                    return false;
                }
                return true;
            },
            requestGender: function () {
                var aTriggerCountries = ['DE', 'NL', 'AT'];
                if (this.getBillingCountry() !== false && aTriggerCountries.indexOf(this.getBillingCountry()) !== -1) {
                    if (window.checkoutConfig.payment.payone.customerHasGivenGender == false) {
                        return true;
                    }
                }
                return false;
            },
            requestPersonalId: function () {
                var aTriggerCountries = ['DK', 'FI', 'NO', 'SE'];
                if (this.getBillingCountry() !== false && aTriggerCountries.indexOf(this.getBillingCountry()) !== -1) {
                    return true;
                }
                return false;
            },
            requestBirthday: function () {
                var aTriggerCountries = ['DE', 'NL', 'AT'];
                if (this.getBillingCountry() !== false && aTriggerCountries.indexOf(this.getBillingCountry()) !== -1) {
                    if (window.checkoutConfig.payment.payone.customerBirthday === false) {
                        return true;
                    }
                }
                return false;
            },
            requestAgreement: function () {
                var aTriggerCountries = ['DE', 'AT'];
                if (this.getBillingCountry() !== false && aTriggerCountries.indexOf(this.getBillingCountry()) !== -1) {
                    return true;
                }
                return false;
            },
            getBillingCountry: function () {
                if (quote.billingAddress() !== null && typeof quote.billingAddress().countryId !== 'undefined') {
                    return quote.billingAddress().countryId;
                }
                return false;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.telephone = this.telephone();
                parentReturn.additional_data.addinfo = this.addinfo();
                parentReturn.additional_data.del_addinfo = this.delAddinfo();
                parentReturn.additional_data.gender = this.gender();
                parentReturn.additional_data.personal_id = this.personalId();
                parentReturn.additional_data.birthday = this.birthday();
                parentReturn.additional_data.birthmonth = this.birthmonth();
                parentReturn.additional_data.birthyear = this.birthyear();
                return parentReturn;
            },
            getKlarnaStoreId: function () {
                var storeIds = window.checkoutConfig.payment.payone.klarnaStoreIds;
                var country = this.getBillingCountry();
                if (storeIds.hasOwnProperty(country)) {
                    return storeIds[country];
                }
                return 0;
            },
            getKlarnaUrl: function (sLanguage) {
                return 'https://cdn.klarna.com/1.0/shared/content/legal/terms/' + this.getKlarnaStoreId() + '/de_' + sLanguage + '/consent';
            },
            validate: function () {
                if (this.requestAgreement() === true && this.agreement() === false) {
                    this.messageContainer.addErrorMessage({'message': $t('Please confirm the transmission of the necessary data to Klarna!')});
                    return false;
                }
                return true;
            },
            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            loadKlarnaTerms: function () {
                var oElement = document.getElementById('payone_klarna_invoice_terms');
                if (oElement.innerHTML.length === 0) {
                    var terms = new Klarna.Terms.Invoice({
                        el: oElement,
                        eid: this.getKlarnaStoreId(),  // Your merchant ID
                        country: this.getBillingCountry().toLowerCase(),
                        charge: 0
                    });
                }
            }
        });
    }
);
