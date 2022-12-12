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
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (clientToken, payment, paymentCode, paymentMethodCategory, containerId) {
        window.klarnaAsyncCallback = function () {
            Klarna.Payments.init({
                client_token: clientToken
            });

            Klarna.Payments.load({
                container: '#' + containerId,
                payment_method_category: paymentMethodCategory
            }, function (result) {
                if (result.show_form === true) {
                    payment.canBeAuthorized = true;
                    payment.isPlaceOrderActionAllowed(true);
                    $('#' + containerId).show();
                    $('#klarna_placeOrder').show();
                } else if(result.show_form === false && result.error == undefined) {
                    payment.messageContainer.addErrorMessage({'message': $t('Klarna payment can not be offered for this order.')});
                    $('#' + paymentCode + '_check').prop( "disabled", true );
                }
            });
        };
        $.getScript('https://x.klarnacdn.net/kp/lib/v1/api.js');
    };
});
