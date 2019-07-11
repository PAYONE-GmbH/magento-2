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
 * @copyright 2003 - 2018 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'mage/url',
        'mage/utils/wrapper',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/method-list'
    ],
    function ($, url, wrapper, globalMessageList, methodList) {
        'use strict';

        return function (targetModule) {
            targetModule.disablePaymentType = function (sPaymentType) {
                $('INPUT#' + sPaymentType).parents('.payment-method').fadeOut(2000, function() {
                    $('INPUT#' + sPaymentType).parents('.payment-method').remove();
                });
            };

            targetModule.process = wrapper.wrap(targetModule.process, function (originalAction, response, messageContainer) {
                var origReturn = originalAction(response, messageContainer);

                if (response.responseJSON.hasOwnProperty('parameters') && response.responseJSON.parameters.hasOwnProperty('paymentMethodWhitelist') && response.responseJSON.parameters.paymentMethodWhitelist.length > 0) {
                    $.each(methodList(), function( key, value ) {
                        if (response.responseJSON.parameters.paymentMethodWhitelist.includes(value.method) === false) {
                            targetModule.disablePaymentType(value.method);
                        }
                    });
                }
                return origReturn;
            });

            // only extend if the option was enabled
            if (window.checkoutConfig.payment.payone.disableSafeInvoice === true) {
                targetModule.process = wrapper.wrap(targetModule.process, function (originalAction, response, messageContainer) {
                    var origReturn = originalAction(response, messageContainer);

                    if (response.status != 401) {
                        if(response.responseJSON.message.indexOf('351 -') !== -1) {
                            targetModule.disablePaymentType('payone_safe_invoice');
                        }
                    }
                    return origReturn;
                });
            }
            return targetModule;
        };
    }
);
