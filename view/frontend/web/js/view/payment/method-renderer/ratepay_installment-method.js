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
define(
    [
        'Payone_Core/js/view/payment/method-renderer/ratepay_base',
        'Magento_Checkout/js/model/quote',
        'Payone_Core/js/action/installmentplanratepay',
        'Magento_Catalog/js/price-utils',
        'jquery',
        'mage/translate'
    ],
    function (Component, quote, installmentplan, utils, $, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payone_Core/payment/ratepay_installment',
                birthday: '',
                birthmonth: '',
                birthyear: '',
                telephone: '',
                iban: '',
                bic: '',
                installmentPlanSet: false,
                installmentNumber: null,
                installmentAmount: null,
                installmentLastAmount: null,
                installmentTotalAmount: null,
                interestRate: null,
                useDirectDebit: true,

            },
            initObservable: function () {
                this._super()
                    .observe([
                        'birthday',
                        'birthmonth',
                        'birthyear',
                        'telephone',
                        'iban',
                        'bic',
                        'installmentPlanSet',
                        'installmentNumber',
                        'installmentAmount',
                        'installmentLastAmount',
                        'installmentTotalAmount',
                        'interestRate',
                        'useDirectDebit'
                    ]);
                return this;
            },
            getData: function () {
                var parentReturn = this._super();

                parentReturn.additional_data.iban = this.iban();
                if (this.requestBic()) {
                    parentReturn.additional_data.bic = this.bic();
                }

                parentReturn.additional_data.installment_amount = this.installmentAmount();
                parentReturn.additional_data.installment_number = this.installmentNumber();
                parentReturn.additional_data.last_installment_amount = this.installmentLastAmount();
                parentReturn.additional_data.interest_rate = this.interestRate();
                parentReturn.additional_data.amount = this.installmentTotalAmount();

                return parentReturn;
            },
            validate: function () {
                var parentReturn = this._super();

                if (this.useDirectDebit() === true && this.iban() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid IBAN.')});
                    return false;
                }
                if (this.useDirectDebit() === true && this.requestBic() && this.bic() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid BIC.')});
                    return false;
                }
                if (this.installmentPlanSet() === false) {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid BIC.')});
                    return false;
                }

                return parentReturn;
            },
            isPlaceOrderActionAllowedRatePayInstallment: function () {
                var parentReturn = this.isPlaceOrderActionAllowedRatePay();

                if (this.installmentPlanSet() === false) {
                    return false;
                }

                return parentReturn;
            },
            updateInstallmentPlanByAmount: function () {
                installmentplan(this, 'calculation-by-rate', $("#" + this.getCode() + "-rate").val());
            },
            updateInstallmentPlanByTimeDropdown: function (data, event) {
                this.updateInstallmentPlanByTime(event.target.value);
            },
            updateInstallmentPlanByTime: function (iMonths) {
                installmentplan(this, 'calculation-by-time', iMonths);
            },
            updateInstallmentPlan: function (installmentPlan) {
                $("#rp_installment_amount").html(utils.formatPrice(installmentPlan.amount, window.checkoutConfig.priceFormat));
                $("#rp_installment_service_charge").html(utils.formatPrice(installmentPlan.service_charge, window.checkoutConfig.priceFormat));
                $("#rp_installment_annual_percentage_rate").html(installmentPlan.annual_percentage_rate + " %");
                $("#rp_installment_interest_rate").html(installmentPlan.interest_rate + " %");
                $("#rp_installment_interest_amount").html(utils.formatPrice(installmentPlan.interest_amount, window.checkoutConfig.priceFormat));
                $("#rp_installment_number_of_rates").html(parseInt(installmentPlan.number_of_rates) - 1);
                $("#rp_installment_rate_details").html(utils.formatPrice(installmentPlan.rate, window.checkoutConfig.priceFormat));
                $("#rp_installment_last_rate").html(utils.formatPrice(installmentPlan.last_rate, window.checkoutConfig.priceFormat));
                $("#rp_installment_number_of_rates_full").html(installmentPlan.number_of_rates);
                $("#rp_installment_rate").html(utils.formatPrice(installmentPlan.rate, window.checkoutConfig.priceFormat));
                $("#rp_installment_total_amount").html(utils.formatPrice(installmentPlan.total_amount, window.checkoutConfig.priceFormat));

                $(".ratepayInstallmentPlan").show();

                this.installmentAmount(installmentPlan.rate);
                this.installmentLastAmount(installmentPlan.last_rate);
                this.installmentNumber(installmentPlan.number_of_rates);
                this.interestRate(installmentPlan.interest_rate);
                this.installmentTotalAmount(installmentPlan.total_amount);
            },
            toggleInstallmentPlanDetails: function () {
                $(".installmentToggle").toggle();
            },
            togglePaytype: function () {
                $(".paytypeToggle").toggle();
                this.useDirectDebit(!this.useDirectDebit());
            },
            getAllowedMonths: function () {
                return window.checkoutConfig.payment.payone.ratepayAllowedMonths;
            },
            useMonthDropdown: function () {
                if (this.getAllowedMonths().length > 9) {
                    return true;
                }
                return false;
            }
        });
    }
);
