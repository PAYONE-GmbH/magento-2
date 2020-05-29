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
 * @copyright 2003 - 2020 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'Magento_Checkout/js/model/quote'
], function ($, quote) {
    'use strict';

    var mixin = {
        isBaseGrandTotalDisplayNeeded: function () {
            var parentReturn = this._super();
            if (window.checkoutConfig.payment.payone.currency === "display" && parentReturn === true && quote.paymentMethod()) {
                if (quote.paymentMethod().method.indexOf('payone') !== -1) {
                    $('.opc-block-summary .totals.charge').hide();
                } else {
                    $('.opc-block-summary .totals.charge').show();
                }
            }
            return this._super();
        }
    };

    return function (grand_total) {
        return grand_total.extend(mixin);
    };
});
