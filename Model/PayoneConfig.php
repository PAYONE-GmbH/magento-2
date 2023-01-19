<?php

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

namespace Payone\Core\Model;

/**
 * Collection of constant values
 */
abstract class PayoneConfig
{
    /* Module version */
    const MODULE_VERSION = '3.7.0';

    /* Authorization request types */
    const REQUEST_TYPE_PREAUTHORIZATION = 'preauthorization';
    const REQUEST_TYPE_AUTHORIZATION = 'authorization';

    /* Cvc checktypes */
    const CREDITCARD_CHECK_CVC_NO = 'no';
    const CREDITCARD_CHECK_CVC_ONLY_FIRST = 'only_first';
    const CREDITCARD_CHECK_CVC_ALWAYS = 'always';

    /* Bankaccount checktypes */
    const BANKACCOUNT_CHECKTYPE_REGULAR = '0';
    const BANKACCOUNT_CHECKTYPE_POS_BLACKLIST = '1';

    /* Transactionstatus possibilities */
    const TRANSACTIONSTATUS_APPOINTED = 'appointed';
    const TRANSACTIONSTATUS_CAPTURE = 'capture';
    const TRANSACTIONSTATUS_PAID = 'paid';
    const TRANSACTIONSTATUS_UNDERPAID = 'underpaid';
    const TRANSACTIONSTATUS_CANCELATION = 'cancelation';
    const TRANSACTIONSTATUS_REFUND = 'refund';
    const TRANSACTIONSTATUS_DEBIT = 'debit';
    const TRANSACTIONSTATUS_REMINDER = 'reminder';
    const TRANSACTIONSTATUS_VAUTHORIZATION = 'vauthorization';
    const TRANSACTIONSTATUS_VSETTLEMENT = 'vsettlement';
    const TRANSACTIONSTATUS_TRANSFER = 'transfer';
    const TRANSACTIONSTATUS_INVOICE = 'invoice';

    /* Payment method codes */
    const METHOD_CREDITCARD = 'payone_creditcard';
    const METHOD_CASH_ON_DELIVERY = 'payone_cash_on_delivery';
    const METHOD_DEBIT = 'payone_debit';
    const METHOD_ADVANCE_PAYMENT = 'payone_advance_payment';
    const METHOD_INVOICE = 'payone_invoice';
    const METHOD_PAYPAL = 'payone_paypal';
    const METHOD_OBT_SOFORTUEBERWEISUNG = 'payone_obt_sofortueberweisung';
    const METHOD_OBT_GIROPAY = 'payone_obt_giropay';
    const METHOD_OBT_EPS = 'payone_obt_eps';
    const METHOD_OBT_POSTFINANCE_EFINANCE = 'payone_obt_postfinance_efinance';
    const METHOD_OBT_POSTFINANCE_CARD = 'payone_obt_postfinance_card';
    const METHOD_OBT_IDEAL = 'payone_obt_ideal';
    const METHOD_OBT_PRZELEWY = 'payone_obt_przelewy';
    const METHOD_KLARNA = 'payone_klarna'; // method was deleted but constant is still useful for unit tests
    const METHOD_BARZAHLEN = 'payone_barzahlen';
    const METHOD_PAYDIREKT = 'payone_paydirekt';
    const METHOD_SAFE_INVOICE = 'payone_safe_invoice';
    const METHOD_PAYOLUTION_INVOICE = 'payone_payolution_invoice';
    const METHOD_PAYOLUTION_DEBIT = 'payone_payolution_debit';
    const METHOD_PAYOLUTION_INSTALLMENT = 'payone_payolution_installment';
    const METHOD_KLARNA_BASE = 'payone_klarna_base';
    const METHOD_KLARNA_INVOICE = 'payone_klarna_invoice';
    const METHOD_KLARNA_DEBIT = 'payone_klarna_debit';
    const METHOD_KLARNA_INSTALLMENT = 'payone_klarna_installment';
    const METHOD_ALIPAY = 'payone_alipay';
    const METHOD_WECHATPAY = 'payone_wechatpay';
    const METHOD_AMAZONPAY = 'payone_amazonpay';
    const METHOD_RATEPAY_INVOICE = 'payone_ratepay_invoice';
    const METHOD_RATEPAY_INSTALLMENT = 'payone_ratepay_installment';
    const METHOD_RATEPAY_DEBIT = 'payone_ratepay_debit';
    const METHOD_TRUSTLY = 'payone_trustly';
    const METHOD_APPLEPAY = 'payone_applepay';
    const METHOD_BANCONTACT = 'payone_bancontact';
    const METHOD_BNPL_INVOICE = 'payone_bnpl_invoice';
    const METHOD_BNPL_INSTALLMENT = 'payone_bnpl_installment';
    const METHOD_BNPL_DEBIT = 'payone_bnpl_debit';

    /* Payment method group identifiers */
    const METHOD_GROUP_ONLINE_BANK_TRANSFER = 'payone_online_bank_transfer';
    const METHOD_GROUP_PAYOLUTION = 'payone_payolution';
    const METHOD_GROUP_KLARNA = 'payone_klarna';
    const METHOD_GROUP_RATEPAY = 'payone_ratepay';
    const METHOD_GROUP_BNPL = 'payone_bnpl';
}
