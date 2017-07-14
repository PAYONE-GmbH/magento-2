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
        'mage/translate'
    ],
    function (Component, $t) {
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
            requestBirthday: function () {
                if (window.checkoutConfig.payment.payone.customerHasGivenBirthday == false) {
                    return true;
                }
                return false;
            },
            isCustomerTooYoung: function () {
                var sBirthDate = this.birthyear() + "-" + this.birthmonth() + "-" + this.birthday();
                var oBirthDate = new Date(sBirthDate);
                var oMinDate = new Date(new Date().setYear(new Date().getFullYear() - 18));
                if(oBirthDate < oMinDate) {
                    return false;
                }
                return true;
            },
            validate: function () {
                if (this.isCustomerTooYoung()) {
                    this.messageContainer.addErrorMessage({'message': $t('You have to be at least 18 years old to use this payment type!')});
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
