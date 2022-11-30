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
 * PHP version 8
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2022 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Payone_Core/js/view/payment/method-renderer/bnpl_base',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'Payone_Core/js/action/installmentplanbnpl',
        'Magento_Checkout/js/model/payment/additional-validators',
        'jquery'
    ],
    function (Component, quote, $t, installmentplan, additionalValidators, $) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/bnpl_installment',
                birthday: '',
                birthmonth: '',
                birthyear: '',
                telephone: '',
                bankaccountholder: '',
                iban: '',
                optionid: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'birthday',
                        'birthmonth',
                        'birthyear',
                        'telephone',
                        'telephone',
                        'bankaccountholder',
                        'iban',
                        'optionid'
                    ]);
                return this;
            },
            isPlaceOrderActionAllowedBNPLInstallment: function () {
                return (this.optionid() != '' && this.isPlaceOrderActionAllowedBNPL());
            },
            selectPaymentMethod: function () {
                var returnValue = this._super();
                this.handleInstallment();
                return returnValue;
            },
            handleInstallment: function () {
                window.bnpl_installment = this;
                window.switchBNPLInstallmentPlan = window.switchBNPLInstallmentPlan || function (sKey, sCode, iInstallments, linkElement) {
                    window.bnpl_installment.switchBNPLInstallmentPlan(sKey, sCode, iInstallments, linkElement);
                };
                installmentplan(this, this.getCode());
            },
            switchBNPLInstallmentPlan: function (sKey, sCode, installmentOptionId, linkElement) {
                $('.bnplCondition').css('font-weight', 'normal');
                $(linkElement).css('font-weight', 'bold');
                $('#bnplCondition_' + sKey).prop("checked", true);
                $('.bnpl_installmentplans').hide();
                $('.bnpl_installment_overview').hide();

                $('#bnpl_installmentplan_' + sKey).show();
                $('#bnpl_installment_overview_' + sKey).show();

                this.optionid(installmentOptionId);
            },
            getData: function () {
                var parentReturn = this._super();
                parentReturn.additional_data.optionid = this.optionid();
                parentReturn.additional_data.bankaccountholder = this.bankaccountholder();
                parentReturn.additional_data.iban = this.getCleanedNumber(this.iban());
                return parentReturn;
            },
            validate: function () {
                var parentReturn = this._super();
                if (parentReturn === false) {
                    return parentReturn;
                }
                if (this.bankaccountholder() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter your bank account holder information.')});
                    return false;
                }
                if (this.iban() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid IBAN.')});
                    return false;
                }
                return parentReturn;
            },
            displayInstallmentInfo(installmentplan) {
                $('#' + this.getCode() + '_installmentplan').html(installmentplan.installment_plan_html);
                $('#' + this.getCode() + '_installmentplan').show();
                $('#' + this.getCode() + '_check').hide();
                $('#' + this.getCode() + '_submit').show();
            }
        });
    }
);
