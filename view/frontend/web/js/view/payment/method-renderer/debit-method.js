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
        'Payone_Core/js/view/payment/method-renderer/base',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (Component, messageList, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/debit',
                bankCountry: '',
                iban: '',
                bic: ''
            },
            
            initObservable: function () {
                this._super()
                    .observe([
                        'bankCountry',
                        'iban',
                        'bic'
                    ]);
                return this;
            },

            validate: function () {
                if (document.getElementById(this.getCode() + '_country').value == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please choose the bank country.')});
                    return false;
                }
                if (this.iban() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid IBAN.')});
                    return false;
                }
                if (this.requestBic() == 1 && this.bic() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid BIC.')});
                    return false;
                }
                return true;
            },
            
            handleBankaccountCheck: function () {
                var oBasicRequest = window.checkoutConfig.payment.payone.bankaccountcheckRequest;
                oBasicRequest.iban = this.iban();
                if (this.requestBic() == 1) {
                    oBasicRequest.bic = this.bic();
                }

                window.elvjs = this;
                window.processPayoneResponseELV = window.processPayoneResponseELV || function (response) {
                        window.elvjs.processPayoneResponseELV(response);
                    };

                var options = {
                    return_type : 'object',
                    callback_function_name : 'processPayoneResponseELV'
                };

                var request = new PayoneRequest(oBasicRequest, options);
                request.checkAndStore();
            },

            /** Returns payment method instructions */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            
            getCountries: function () {
                return window.checkoutConfig.payment.payone.sepaCountries;
            },
            getCleanedNumber: function (sDirtyNumber) {
                var sCleanedNumber = '';
                var sTmpChar;
                for (var i = 0; i < sDirtyNumber.length; i++) {
                    sTmpChar = sDirtyNumber.charAt(i);
                    if (sTmpChar != ' ' && (!isNaN(sTmpChar) || /^[A-Za-z]/.test(sTmpChar))) {
                        if (/^[a-z]/.test(sTmpChar)) {
                            sTmpChar = sTmpChar.toUpperCase();
                        }
                        sCleanedNumber = sCleanedNumber + sTmpChar;
                    }
                }
                return sCleanedNumber;
            },
            
            getData: function () {
                document.getElementById(this.getCode() + '_iban').value = this.getCleanedNumber(this.iban());
                if (this.requestBic() == 1) {
                    document.getElementById(this.getCode() + '_bic').value = this.getCleanedNumber(this.bic());
                }
                
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.bank_country = this.bankCountry();
                parentReturn.additional_data.iban = this.getCleanedNumber(this.iban());
                parentReturn.additional_data.bic = this.getCleanedNumber(this.bic());
                return parentReturn;
            },
            
            isManageMandateActive: function () {
                return window.checkoutConfig.payment.payone.mandateManagementActive;
            },
            requestBic: function () {
                return window.checkoutConfig.payment.payone.requestBic;
            },
            processPayoneResponseELV: function (response) {
                if (response.get('status') === "VALID") {
                    window.checkoutConfig.payment.payone.bankCodeValidatedAndValid = true;
                    this.handleSetPaymentInformation('payone/onepage/debit/');
                } else if (true || response.get('status') === "BLOCKED") {
                    this.messageContainer.addErrorMessage({'message': window.checkoutConfig.payment.payone.blockedMessage});
                } else {
                    this.messageContainer.addErrorMessage({'message': $t(response.get('customermessage'))});
                }
            }
        });
    }
);
