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
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'Payone_Core/js/action/addresscheck',
    'Magento_Customer/js/model/customer',
], function ($, addresscheck, customer) {
    'use strict';

    var mixin = {
        payoneCheckAddress: function () {
            if (window.checkoutConfig.payment.payone.addresscheckEnabled && window.checkoutConfig.payment.payone.addresscheckBillingEnabled) {
                return true;
            }
            return false;
        },
        updateAddress: function () {
            if (customer.isLoggedIn() && (!this.payoneCheckAddress() || !(this.selectedAddress() && this.selectedAddress() != this.newAddressOption))) {
                return this._super();
            }

            var addressChecked = this.source.get('payone_address_checked');
            if (!addressChecked) {
                var address = this.source.get(this.dataScopePrefix);
                if (!this.isAddressFormVisible()) {
                    address = this.selectedAddress()
                }
                addresscheck(address, true, this, 'saveNewAddress');
            } else {
                this.source.set('payone_address_checked', false);
                return this._super();
            }
        },
        payoneUpdateAddress: function (addressData) {
            this.source.set(this.dataScopePrefix + '.firstname', addressData.firstname);
            this.source.set(this.dataScopePrefix + '.lastname', addressData.lastname);
            this.source.set(this.dataScopePrefix + '.street.0', addressData.street[0]);
            this.source.set(this.dataScopePrefix + '.postcode', addressData.postcode);
            this.source.set(this.dataScopePrefix + '.city', addressData.city);
        },
        isAddressTheSame: function(addressA, addressB) {
            if (this.getAddressInline(addressA) === this.getAddressInline(addressB)) {
                return true;
            }
            return false;
        },
        getAddressInline: function(address) {
            var street = address.street;
            if (typeof street === 'object') {
                if (street.length === 2) {
                    street = [street[0], street[1]];
                } else {
                    street = Object.values(street);
                }
            }
            var country = address.countryId;
            if (!address.countryId) {
                country = address.country_id
            }
            return address.firstname + address.lastname + street.join("") + address.postcode + address.city + country;
        },
        payoneContinue: function () {
            this.source.set('payone_address_checked', true);
            this.updateAddress();
        }
    }

    return function (billing_address) {
        return billing_address.extend(mixin);
    };
});
