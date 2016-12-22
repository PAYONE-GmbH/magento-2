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
        'ko',
        'jquery',
        'uiComponent'
    ],
    function (ko, $, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/boni-agreement'
            },
            
            isVisible: function () {
                if (this.canShowPaymentHintText() || this.canShowAgreementMessage()) {
                    return true;
                }
                return false;
            },
            canShowPaymentHintText: function () {
                return window.checkoutConfig.payment.payone.canShowPaymentHintText;
            },
            getPaymentHintText: function () {
                return window.checkoutConfig.payment.payone.paymentHintText;
            },
            canShowAgreementMessage: function () {
                return window.checkoutConfig.payment.payone.canShowAgreementMessage;
            },
            getAgreementMessage: function () {
                return window.checkoutConfig.payment.payone.agreementMessage;
            }
        });
    }
);
