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
    'jquery'
], function ($) {
    'use strict';

    var mixin = {
        getData: function () {
            var parentReturn = this._super();
            if ($('#payone_boni_agreement') && $('#payone_boni_agreement')[0]) {
                var blAgreed = false;
                if ($('#payone_boni_agreement')[0].checked) {
                    blAgreed = true;
                }
                
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.payone_boni_agreement = blAgreed;
            }
            return parentReturn;
        },
        afterPlaceOrder: function () {
            //this.redirectAfterPlaceOrder = false;

            return this._super();
        }
    };

    return function (payment) {
        return payment.extend(mixin);
    };
});
