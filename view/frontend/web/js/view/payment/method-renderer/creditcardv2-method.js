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
 * @copyright 2003 - 2026 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */
define(
    [
        'Payone_Core/js/view/payment/method-renderer/base',
        'jquery',
        'mage/translate',
        'Payone_Core/js/action/getjwt',
        'Magento_Checkout/js/checkout-data',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
    ],
    function (Component, $, $t, getJwt, checkoutData, messageList, additionalValidators) {
        'use strict';
        return Component.extend({
            /** START MAGENTO CHECKOUT CODE **/
            defaults: {
                template: 'Payone_Core/payment/creditcardv2',
                tokenDataSet: false,
                cardholder: '',
                cardtype: '',
                pseudocardpan: '',
                cardinputmode: '',
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'tokenDataSet',
                        'cardholder',
                        'cardtype',
                        'pseudocardpan',
                        'cardinputmode',
                    ]);
                return this;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.cardholder = this.cardholder();
                parentReturn.additional_data.cardtype = this.cardtype();
                parentReturn.additional_data.pseudocardpan = this.pseudocardpan();
                parentReturn.additional_data.cardinputmode = this.cardinputmode();

                return parentReturn;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            initialize: function () {
                let parentReturn = this._super();
                if (checkoutData.getSelectedPaymentMethod() === this.getCode()) {
                    this.initJavascriptSDK();
                }
                return parentReturn;
            },
            selectPaymentMethod: function () {
                this.initJavascriptSDK();
                return this._super();
            },
            isPlaceOrderActionAllowedClickToPay: function () {
                var parentReturn = this.isPlaceOrderActionAllowed();

                if (this.tokenDataSet() === false) {
                    return false;
                }

                return parentReturn;
            },
            setPaymentObject: function () {
                window.payoneccv2 = this;
            },
            /** END MAGENTO CHECKOUT CODE **/

            /** START ClickToPay SHOW IFRAME CODE **/
            getDpaId: function () {
                return this.getFrontendConfigParam('dpaId');
            },
            getVisaInitiatorId: function () {
                // hardcoded
                return "2662KBGOLX92KS4XIFYU213JLdGTvLhYkOB-_1gLo1D1jOqgM";
            },
            getMastercardInitiatorId: function () {
                // hardcoded
                return "559003b0-5d17-4d89-aa2b-b02a4023d64d";
            },
            getEncryptionKey: function () {
                // hardcoded
                return "GQJIKLOAMZWIT8IRIGHR14vQUlllxiMWf-XSHQHvjI5wuTZ2w";
            },
            getModulus: function () {
                // hardcoded
                return "kPujwVJjevI_oeZwZoA2Wjt94DFcMvRCab8iRiEGrGfKWtNCwQYkylyuRoB615cYm2BVbvoKH8Yyv0aC3dwah6UmOdJszmL0pV_cbx_tXzWgYg3sYNsp0sBxUFcQ1A6DVbyOxxJbmnwlHGE5fkuzJr-qqul3RswsCG-vPrh_--2_RSipa9lVr9gvfI4AbFABLTqKeto0rWPbIBKdhcGQ7JMPxzq8239KPUZfSyNueAcdL-yHADi3L2VSzdF7tS7si3ue_IFoXDpbggsFxvEt79UlBDOBsagc_ms9_ZsYlJaKCT8ZjwhakMo_-Zdc97mudVj1jz2_L5l4l_zibF5riw";
            },
            getSchemeConfig: function () {
                let schemeConfig = {
                    merchantPresentationName: "PayoneC2P-00004",
                    visaConfig: {
                        srcInitiatorId: this.getVisaInitiatorId(),
                        srcDpaId: this.getDpaId(),
                        encryptionKey: this.getEncryptionKey(),
                        nModulus: this.getModulus(),
                    },
                    mastercardConfig: {
                        srcInitiatorId: this.getMastercardInitiatorId(),
                        srcDpaId: this.getDpaId(),
                    }
                };
                return schemeConfig;
            },
            getUiConfig: function () {
                let uiConfig = {};
                if (this.getFrontendConfigParam('uiConfigCustomizationEnabled') === "1") {
                    uiConfig.formBgColor = this.getFrontendConfigParam('uiConfigFormBgColor');
                    uiConfig.fieldBgColor = this.getFrontendConfigParam('uiConfigFieldBgColor');
                    uiConfig.fieldBorder = this.getFrontendConfigParam('uiConfigFieldBorder');
                    uiConfig.fieldOutline = this.getFrontendConfigParam('uiConfigFieldOutline');
                    uiConfig.fieldLabelColor = this.getFrontendConfigParam('uiConfigFieldLabelColor');
                    uiConfig.fieldPlaceholderColor = this.getFrontendConfigParam('uiConfigFieldPlaceholderColor');
                    uiConfig.fieldTextColor = this.getFrontendConfigParam('uiConfigFieldTextColor');
                    uiConfig.fieldErrorCodeColor = this.getFrontendConfigParam('uiConfigFieldErrorCodeColor');
                }
                return uiConfig;
            },
            getCTPUiConfig: function () {
                let uiConfig = {};
                if (this.getFrontendConfigParam('uiConfigCustomizationEnabled') === "1") {
                    uiConfig.buttonStyle = this.getFrontendConfigParam('uiConfigButtonStyle');
                    uiConfig.buttonTextCase = this.getFrontendConfigParam('uiConfigButtonTextCase');
                    uiConfig.buttonAndBadgeColor = this.getFrontendConfigParam('uiConfigButtonAndBadgeColor');
                    uiConfig.buttonFilledHoverColor = this.getFrontendConfigParam('uiConfigButtonFilledHoverColor');
                    uiConfig.buttonOutlinedHoverColor = this.getFrontendConfigParam('uiConfigButtonOutlinedHoverColor');
                    uiConfig.buttonDisabledColor = this.getFrontendConfigParam('uiConfigButtonDisabledColor');
                    uiConfig.cardItemActiveColor = this.getFrontendConfigParam('uiConfigCardItemActiveColor');
                    uiConfig.buttonAndBadgeTextColor = this.getFrontendConfigParam('uiConfigButtonAndBadgeTextColor');
                    uiConfig.linkTextColor = this.getFrontendConfigParam('uiConfigLinkTextColor');
                    uiConfig.accentColor = this.getFrontendConfigParam('uiConfigAccentColor');
                    uiConfig.fontFamily = this.getFrontendConfigParam('uiConfigFontFamily');
                    uiConfig.buttonAndInputRadius = this.getFrontendConfigParam('uiConfigButtonAndInputRadius');
                    uiConfig.cardItemRadius = this.getFrontendConfigParam('uiConfigCardItemRadius');
                }
                return uiConfig;
            },
            getIsCTPEnabled: function () {
                if (this.getFrontendConfigParam('ctpEnabled') === "1") {
                    return true;
                }
                return false;
            },
            getIsCTPRegisterEnabled: function () {
                if (this.getFrontendConfigParam('ctpRegisterEnabled') === "1") {
                    return true;
                }
                return false;
            },
            getCTPShopName: function () {
                let shopName = this.getFrontendConfigParam('ctpShopName');
                if (shopName) {
                    return shopName;
                }
                return "";
            },
            getConfig: async function (jwtToken) {
                let config = {
                    iframe: {
                        iframeWrapperId: this.getIframeContainerId(),
                        zIndex: 10000,
                        height: 500,
                        width: 400
                    },
                    uiConfig: this.getUiConfig(),
                    locale: window.checkoutConfig.payment.payone.fullLocale,
                    token: jwtToken,
                    mode: this.getFrontendConfigParam('mode'),
                    allowedCardSchemes: [
                        "visa",
                        "mastercard",
                        "amex",
                        "diners",
                        "jcb",
                        "discover",
                        "maestro",
                        "unionpay",
                    ],
                    CTPConfig: {
                        enableCTP: this.getIsCTPEnabled(),
                        enableCustomerOnboarding: this.getIsCTPRegisterEnabled(),
                        schemeConfig: this.getSchemeConfig(),
                        transactionAmount: {
                            amount: this.getOrderTotalForAPI(),
                            currencyCode: this.getCurrency(),
                        },
                        uiConfig: this.getCTPUiConfig(),
                        shopName: this.getCTPShopName(),
                        token: jwtToken,
                    }
                };
                return config;
            },
            handleTokenError: function () {
                console.error("JWT Token could not be retrieved");
                var paymentMethod = this.getCode();
                this.messageContainer.addErrorMessage({'message': $t('There has been a technical error. Please choose another payment method.')});
                $('INPUT#' + paymentMethod).parents('.payment-method').delay(7000).fadeOut(2000, function() {
                    $('INPUT#' + paymentMethod).parents('.payment-method').remove();
                });
            },
            initJavascriptSDK: async function () {
                if (window.HostedTokenizationSdk || document.getElementById('payone-sdk')) {
                    return;
                }

                let jwtTokenResponse = await getJwt();
                if (!jwtTokenResponse || jwtTokenResponse.success === false || !jwtTokenResponse.jwt) {
                    this.handleTokenError();
                    return;
                }

                const jwtToken = jwtTokenResponse.jwt;

                this.isPlaceOrderActionAllowed(false);

                // IMPORTANT: Disable RequireJS temporarily to prevent the SDK from reporting itself as an anonymous module
                var _oldDefine = window.define;
                window.define = undefined;

                var script = document.createElement('script');
                script.id = 'payone-sdk';
                script.src = this.getJsSDKUrl();
                //script.integrity = 'sha384-oga+IGWvy3VpUUrebY+BnLYvsNZRsB3NUCMSa+j3CfA9ePHUZ++8/SVyim9F7Jm3'; // disabled because only working for script in version 1.2.1
                script.async = true;
                script.crossOrigin = "anonymous";

                var self = this;

                script.onload = function () {
                    // Restore the original define function
                    window.define = _oldDefine;

                    if (window.HostedTokenizationSdk) {
                        self.initPaymentPage(jwtToken);
                    } else {
                        console.error("HTP-SDK failed to load");
                    }
                };

                script.onerror = function () {
                    window.define = _oldDefine;
                    console.error("Error loading HTP-SDK.");
                };

                document.head.appendChild(script);
            },
            initPaymentPage: async function (jwtToken) {
                try {
                    await window.HostedTokenizationSdk.init();
                    let config = await this.getConfig(jwtToken);

                    this.setPaymentObject();
                    window.HostedTokenizationSdk.getPaymentPage(config, this.callbackFunc);
                } catch (error) {
                    console.error('Error initializing HTP-SDK:', error);
                }
            },
            getIframeContainerId: function () {
                return 'payone-ccv2-iframe';
            },
            getJsSDKUrl: function () {
                return 'https://sdk.tokenization.secure.payone.com/1.3.0/hosted-tokenization-sdk.js';
            },
            /** END ClickToPay SHOW IFRAME CODE **/

            /** START ClickToPay STATUS EVENTS **/
            callbackFunc: function (statusCode, res) {
                if (statusCode === "ReadyToPay" && res === 0) { // Behaviour not documented like this, but it seems like these in combination mean "ready to pay"
                    window.payoneccv2.isPlaceOrderActionAllowed(true);
                    window.payoneccv2.tokenDataSet(true);
                }
            },
            subtmitClickToPay: function () {
                this.setPaymentObject();

                window.HostedTokenizationSdk.submitForm(this.tokenizationSuccessCallback, this.tokenizationFailureCallback);
            },
            handleCreditcardPayment: function () {
                var firstValidation = additionalValidators.validate();
                if (!(firstValidation)) {
                    return false;
                }

                if (this.validate() && firstValidation && this.pseudocardpan()) {
                    this.handleRedirectAction('payone/onepage/redirect/');
                    return false;
                }
            },
            tokenizationSuccessCallback: function (statusCode, token, cardDetails, cardInputMode) {
                // this variable is missing in this context
                let self = window.payoneccv2;

                self.cardholder(cardDetails.cardholderName);
                self.cardtype(cardDetails.cardType);
                self.pseudocardpan(token);
                self.cardinputmode(cardInputMode);

                self.handleCreditcardPayment();
            },
            tokenizationFailureCallback: function (statusCode, errorResponse) {
                // This is where you can handle errors that can happen during the tokenization (e.g. expired JWTs or malformated JWTs)
                console.error("Tokenization of card failed");
                console.log(statusCode); // e.g. 400
                console.log(errorResponse.error); // optional e.g. "JWT structure is incorrect."
            }
            /** END ClickToPay STATUS EVENTS **/
        });
    }
);
