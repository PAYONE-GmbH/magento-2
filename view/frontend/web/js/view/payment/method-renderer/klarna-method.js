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
        'Magento_Checkout/js/model/quote'
    ],
    function (Component, quote) {
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
                birthyear: ''
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
                        'birthyear'
                    ]);
                return this;
            },
            requestTelephone: function () {
                if (quote.billingAddress() != null && typeof quote.billingAddress().telephone != 'undefined' && quote.billingAddress().telephone != '') {
                    return false;
                }
                return true;
            },
            requestAddressAddInfo: function () {
                if (quote.billingAddress() != null && typeof quote.billingAddress().countryId != 'undefined' && quote.billingAddress().countryId != 'NL') {
                    return false;
                }
                return true;
            },
            requestDelAddressAddInfo: function () {
                if (quote.billingAddress() != null && typeof quote.billingAddress().countryId != 'undefined' && quote.billingAddress().countryId != 'NL') {
                    return false;
                }
                return true;
            },
            requestGender: function () {
                var aTriggerCountries = ['DE', 'NL', 'AT'];
                if (quote.billingAddress() != null && typeof quote.billingAddress().countryId != 'undefined' && aTriggerCountries.indexOf(quote.billingAddress().countryId) != -1) {
                    if (window.checkoutConfig.payment.payone.customerHasGivenGender == false) {
                        return true;
                    }
                }
                return false;
            },
            requestPersonalId: function () {
                var aTriggerCountries = ['DK', 'FI', 'NO', 'SE'];
                if (quote.billingAddress() != null && typeof quote.billingAddress().countryId != 'undefined' && aTriggerCountries.indexOf(quote.billingAddress().countryId) != -1) {
                    return true;
                }
                return false;
            },
            requestBirthday: function () {
                var aTriggerCountries = ['DE', 'NL', 'AT'];
                if (quote.billingAddress() != null && typeof quote.billingAddress().countryId != 'undefined' && aTriggerCountries.indexOf(quote.billingAddress().countryId) != -1) {
                    if (window.checkoutConfig.payment.payone.customerHasGivenBirthday == false) {
                        return true;
                    }
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

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            }
        });
    }
);
