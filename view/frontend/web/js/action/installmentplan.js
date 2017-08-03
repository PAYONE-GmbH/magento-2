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
 * @copyright 2003 - 2017 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function ($, urlBuilder, storage, fullScreenLoader, quote, customer) {
    'use strict';

    /** Override default place order action and add agreement_ids to request */
    return function (baseView, birthday) {
        var serviceUrl;

        var request = {
            birthday: birthday
        };
        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payone-installmentPlan', {
                quoteId: quote.getQuoteId()
            });
            request.email = quote.guestEmail;
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/payone-installmentPlan', {});
        }

        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl,
            JSON.stringify(request)
        ).done(
            function (response) {
                if (response.success == true) {
                    $('#' + baseView.getCode() + '_installmentplan').html(response.installment_plan_html);
                    $('#' + baseView.getCode() + '_installmentplan').show();
                    $('#' + baseView.getCode() + '_check').hide();
                    $('#' + baseView.getCode() + '_submit').show();
                    $('#' + baseView.getCode() + '_birthday_field').hide();
                    $('#' + baseView.getCode() + '_iban_field').show();
                    $('#' + baseView.getCode() + '_bic_field').show();
                } else {
                    alert(response.errormessage);
                }
                fullScreenLoader.stopLoader();
            }
        ).fail(
            function (response) {
                //errorProcessor.process(response, messageContainer);
                alert('An error occured.');
                fullScreenLoader.stopLoader();
            }
        );
    };
});
