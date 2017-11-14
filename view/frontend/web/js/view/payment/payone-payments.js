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
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'payone_creditcard',
                component: 'Payone_Core/js/view/payment/method-renderer/creditcard-method'
            },
            {
                type: 'payone_cash_on_delivery',
                component: 'Payone_Core/js/view/payment/method-renderer/cash_on_delivery-method'
            },
            {
                type: 'payone_debit',
                component: 'Payone_Core/js/view/payment/method-renderer/debit-method'
            },
            {
                type: 'payone_paypal',
                component: 'Payone_Core/js/view/payment/method-renderer/paypal-method'
            },
            {
                type: 'payone_advance_payment',
                component: 'Payone_Core/js/view/payment/method-renderer/advance_payment-method'
            },
            {
                type: 'payone_invoice',
                component: 'Payone_Core/js/view/payment/method-renderer/invoice-method'
            },
            {
                type: 'payone_obt_sofortueberweisung',
                component: 'Payone_Core/js/view/payment/method-renderer/obt_sofortueberweisung-method'
            },
            {
                type: 'payone_obt_giropay',
                component: 'Payone_Core/js/view/payment/method-renderer/obt_giropay-method'
            },
            {
                type: 'payone_obt_eps',
                component: 'Payone_Core/js/view/payment/method-renderer/obt_eps-method'
            },
            {
                type: 'payone_obt_postfinance_efinance',
                component: 'Payone_Core/js/view/payment/method-renderer/obt_postfinance_efinance-method'
            },
            {
                type: 'payone_obt_postfinance_card',
                component: 'Payone_Core/js/view/payment/method-renderer/obt_postfinance_card-method'
            },
            {
                type: 'payone_obt_ideal',
                component: 'Payone_Core/js/view/payment/method-renderer/obt_ideal-method'
            },
            {
                type: 'payone_obt_przelewy',
                component: 'Payone_Core/js/view/payment/method-renderer/obt_przelewy-method'
            },
            {
                type: 'payone_barzahlen',
                component: 'Payone_Core/js/view/payment/method-renderer/barzahlen-method'
            },
            {
                type: 'payone_paydirekt',
                component: 'Payone_Core/js/view/payment/method-renderer/paydirekt-method'
            },
            {
                type: 'payone_billsafe',
                component: 'Payone_Core/js/view/payment/method-renderer/billsafe-method'
            },
            {
                type: 'payone_klarna',
                component: 'Payone_Core/js/view/payment/method-renderer/klarna-method'
            },
            {
                type: 'payone_safe_invoice',
                component: 'Payone_Core/js/view/payment/method-renderer/safe_invoice-method'
            },
            {
                type: 'payone_payolution_invoice',
                component: 'Payone_Core/js/view/payment/method-renderer/payolution_invoice-method'
            },
            {
                type: 'payone_payolution_debit',
                component: 'Payone_Core/js/view/payment/method-renderer/payolution_debit-method'
            },
            {
                type: 'payone_payolution_installment',
                component: 'Payone_Core/js/view/payment/method-renderer/payolution_installment-method'
            },
            {
                type: 'payone_alipay',
                component: 'Payone_Core/js/view/payment/method-renderer/alipay-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
