<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_BankConfig
{

    /**
     * GINGER_ENDPOINT used for create Ginger client
     */
    const GINGER_BANK_ENDPOINT = 'https://api.online.emspay.eu';

    /**
     * BANK_PREFIX and BANK_LABEL used to provide GPE solution
     */
    const BANK_PREFIX = "emspay";
    const BANK_LABEL = "EMS Online";
    const PLUGIN_NAME = "emspay-online-woocommerce";

    /**
     * EMS Online supported payment methods
     */
    public static $BANK_PAYMENT_METHODS = [
        'emspay_ideal',
        'emspay_bank-transfer',
        'emspay_credit-card',
        'emspay_bancontact',
        'emspay_klarna-pay-now',
        'emspay_paypal',
        'emspay_klarna-pay-later',
        'emspay_payconiq',
        'emspay_afterpay',
        'emspay_apple-pay',
        'emspay_pay-now',
        'emspay_amex',
        'emspay_tikkie-payment-request',
        'emspay_wechat',
        'emspay_klarna-direct-debit',
        'emspay_google-pay',
        'emspay_sofort',
        'emspay_giropay',
        'emspay_swish',
        'emspay_mobilepay',
    ];

    /**
     * EMS Online payment methods classnames
     */
    public static $WC_BANK_PAYMENT_METHODS = [
        'WC_Ginger_Callback',
        'WC_Ginger_Ideal',
        'WC_Ginger_Banktransfer',
        'WC_Ginger_Bancontact',
        'WC_Ginger_Creditcard',
        'WC_Ginger_PayPal',
        'WC_Ginger_KlarnaPayLater',
        'WC_Ginger_KlarnaPayNow',
        'WC_Ginger_Payconiq',
        'WC_Ginger_AfterPay',
        'WC_Ginger_ApplePay',
        'WC_Ginger_PayNow',
        'WC_Ginger_Amex',
        'WC_Ginger_TikkiePaymentRequest',
        'WC_Ginger_WeChat',
        'WC_Ginger_Sofort',
        'WC_Ginger_GooglePay',
        'WC_Ginger_KlarnaDirectDebit',
        'WC_Ginger_GiroPay',
        'WC_Ginger_Swish',
        'WC_Ginger_MobilePay',
    ];
}