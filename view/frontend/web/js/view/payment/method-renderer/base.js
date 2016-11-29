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
        'Magento_Checkout/js/view/payment/default',
        'Payone_Core/js/action/handle-redirect',
        'Payone_Core/js/action/handle-debit',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (Component, handleRedirectAction, handleDebitAction, additionalValidators) {
        'use strict';
        return Component.extend({
            continueToPayone: function () {
                if (this.validate() && additionalValidators.validate()) {
                    // update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    handleRedirectAction(this.getData(), this.messageContainer);
                    return false;
                }
            },
            
            handleCreditcardPayment: function () {
                var firstValidation = additionalValidators.validate();
                if (!(firstValidation)) {
                    return false;
                }

                // if (this.validate() && additionalValidators.validate() && document.getElementById(this.getCode() + '_pseudocardpan').value == '') {
                if (this.validate() && firstValidation) {
                        // update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    handleRedirectAction(this.getData(), this.messageContainer);
                    return false;
                } else {
                    this.handleCreditcardCheck();
                }
            },
            
            handleDebitPayment: function () {
                if (this.validate() && additionalValidators.validate()) {
                    if (window.checkoutConfig.payment.payone.validateBankCode == true && window.checkoutConfig.payment.payone.bankCodeValidatedAndValid == false) {
                        this.handleBankaccountCheck();
                    } else {
                        // update payment method information if additional data was changed
                        this.selectPaymentMethod();
                        handleDebitAction(this.getData(), this.messageContainer);
                        return false;
                    }
                }
            }
        });
    }
);
