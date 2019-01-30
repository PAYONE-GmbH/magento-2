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
        'Magento_Ui/js/model/messageList'
    ],
    function ($, url, globalMessageList) {
        'use strict';

        return function (targetModule) {
            // only extend if the option was enabled
            if (window.checkoutConfig.payment.payone.disableSafeInvoice === true) {
                targetModule.process = function (response, messageContainer) {
                    messageContainer = messageContainer || globalMessageList;
                    if (response.status == 401) {
                        window.location.replace(url.build('customer/account/login/'));
                    } else {
                        var error = JSON.parse(response.responseText);
                        messageContainer.addErrorMessage(error);

                        if(response.responseJSON.message.indexOf('351 -') !== -1) {
                            this.disableSafeInvoice();
                        }
                    }
                };
                targetModule.disableSafeInvoice = function () {
                    $('INPUT#payone_safe_invoice').parents('.payment-method').fadeOut(2000, function() {
                        $('INPUT#payone_safe_invoice').parents('.payment-method').remove();
                    });
                };
            }
            return targetModule;
        };
    }
);
