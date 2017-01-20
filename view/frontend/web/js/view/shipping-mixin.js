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
    'Payone_Core/js/action/addresscheck'
], function ($, addresscheck) {
    'use strict';

    var mixin = {
        payoneCheckAddress: function () {
            if (window.checkoutConfig.payment.payone.addresscheckEnabled && window.checkoutConfig.payment.payone.addresscheckShippingEnabled) {
                return true;
            }
            return false;
        },
        saveNewAddress: function () {
            if (!this.payoneCheckAddress()) {
                return this._super();
            }
            
            var addressChecked = this.source.get('payone_address_checked');
            if (!addressChecked) {
                addresscheck(this.source.get('shippingAddress'), false, this, 'saveNewAddress');
            } else {
                this.source.set('payone_address_checked', false);
                return this._super();
            }
        },
        payoneUpdateAddress: function (addressData) {
            this.source.set('shippingAddress.postcode', addressData.postcode);
            this.source.set('shippingAddress.firstname', addressData.firstname);
            this.source.set('shippingAddress.lastname', addressData.lastname);
            this.source.set('shippingAddress.street.0', addressData.street[0]);
            this.source.set('shippingAddress.city', addressData.city);
        },
        payoneContinue: function (sType) {
            if (sType == 'saveNewAddress') {
                this.source.set('payone_address_checked', true);
                this.saveNewAddress();
            } else if (sType == 'setShippingInformation') {
                this.source.set('payone_guest_address_checked', true);
                this.setShippingInformation();
            }
        },
        setShippingInformation: function () {
            if (!this.isFormInline || !this.payoneCheckAddress()) {
                return this._super();
            }
            
            if (!this.source.get('payone_guest_address_checked')) {
                if (this.validateShippingInformation()) {
                    addresscheck(this.source.get('shippingAddress'), false, this, 'setShippingInformation');
                }
            } else {
                this.source.set('payone_guest_address_checked', false);
                return this._super();
            }
        }
    }

    return function (shipping) {
        return shipping.extend(mixin);
    };
});
