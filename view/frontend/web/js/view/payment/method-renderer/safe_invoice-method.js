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
        'mage/translate',
        'Magento_Checkout/js/model/quote'
    ],
    function (Component, $t, quote) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/safe_invoice',
                birthday: '',
                birthmonth: '',
                birthyear: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'birthday',
                        'birthmonth',
                        'birthyear'
                    ]);
                return this;
            },
            isB2bMode: function () {
                if (quote.billingAddress() !== null &&
                    typeof quote.billingAddress().company !== 'undefined' &&
                    quote.billingAddress().company !== '' &&
                    quote.billingAddress().company !== null
                ) {
                    return true;
                }
                return false;
            },
            requestBirthday: function () {
                if (!window.checkoutConfig.payment.payone.customerBirthday && !this.isB2bMode()) {
                    return true;
                }
                return false;
            },
            getBirthDate: function () {
                if (window.checkoutConfig.payment.payone.customerBirthday) {
                    return window.checkoutConfig.payment.payone.customerBirthday;
                }
                return this.birthyear() + "-" + this.birthmonth() + "-" + this.birthday();
            },
            isCustomerTooYoung: function () {
                var oBirthDate = new Date(this.getBirthDate());
                var oMinDate = new Date(new Date().setYear(new Date().getFullYear() - 18));
                if(oBirthDate < oMinDate) {
                    return false;
                }
                return true;
            },
            isCustomerTooOld: function () {
                var oBirthDate = new Date(this.getBirthDate());
                var oMinDate = new Date(new Date().setYear(new Date().getFullYear() - 125)); // max 125 years
                if(oBirthDate > oMinDate) {
                    return false;
                }
                return true;
            },
            isDateInFuture: function () {
                var oBirthDate = new Date(this.getBirthDate());
                var oDateNow = new Date();
                if (oBirthDate > oDateNow) {
                    return true;
                }
                return false;
            },
            isDateInvalid: function () {
                if (!this.birthyear() || isNaN(this.birthyear()) || this.birthyear().length != 4) {
                    return true;
                }
                if (!this.birthmonth() || isNaN(this.birthmonth()) || parseInt(this.birthmonth()) < 1 || parseInt(this.birthmonth()) > 12) {
                    return true;
                }
                if (!this.birthday() || isNaN(this.birthday()) || parseInt(this.birthday()) < 1 || parseInt(this.birthday()) > 31) {
                    return true;
                }
                return false;
            },
            validate: function () {
                if (!this.isB2bMode() && (this.isDateInvalid() || this.isDateInFuture())) {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid birthdate.')});
                    return false;
                }
                if (!this.isB2bMode() && this.isCustomerTooYoung()) {
                    this.messageContainer.addErrorMessage({'message': $t('You have to be at least 18 years old to use this payment type!')});
                    return false;
                }
                if (!this.isB2bMode() && this.isCustomerTooOld()) {
                    this.messageContainer.addErrorMessage({'message': $t('An error occured. Please check the supplied data.')});
                    return false;
                }
                return true;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
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
