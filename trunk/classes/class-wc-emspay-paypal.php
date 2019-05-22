<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_PayPal extends WC_Emspay_Gateway
{
    /**
     * WC_Emspay_Creditcard constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_paypal';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('PayPal - EMS PAY', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('PayPal - EMS PAY', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $emsOrder = $this->ems->createPaypalOrder(
            WC_Emspay_Helper::gerOrderTotalInCents($order),              // Amount in cents
            WC_Emspay_Helper::getCurrency(),                             // currency
            [],
            WC_Emspay_Helper::getOrderDescription($order_id),            // description
            $order_id,                                                   // merchant_order_id
            WC_Emspay_Helper::getReturnUrl(),                            // return_url
            null,                                                        // expiration
            WC_Emspay_Helper::getCustomerInfo($order),                   // customer
            ['plugin' => EMSPAY_PLUGIN_VERSION],                         // extra information
            WC_Emspay_Helper::getWebhookUrl($this)                       // webhook_url
        );

        update_post_meta($order_id, 'ems_order_id', $emsOrder->getId());

        if ($emsOrder->status()->isError()) {
            wc_add_notice(__('There was a problem processing your transaction.', WC_Emspay_Helper::DOMAIN), 'error');
            return [
                'result' => 'failure'
            ];
        }

        WC_Emspay_Helper::reduceStock($order);

        return [
            'result' => 'success',
            'redirect' => $emsOrder->firstTransactionPaymentUrl()->toString()
        ];
    }
}
