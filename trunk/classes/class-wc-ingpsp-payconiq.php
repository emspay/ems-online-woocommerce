<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ingpsp_Payconiq extends WC_Ingpsp_Gateway
{
    /**
     * WC_Ingpsp_Creditcard constructor.
     */
    public function __construct()
    {
        $this->id = 'ingpsp_payconiq';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title =  __('ING PSP - PAYCONIQ', WC_Ingpsp_Helper::DOMAIN);
        $this->method_description = __('ING PSP - PAYCONIQ', WC_Ingpsp_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $ingOrder = $this->ing->createPayconicOrder(
            WC_Ingpsp_Helper::gerOrderTotalInCents($order),              // Amount in cents
            WC_Ingpsp_Helper::getCurrency(),                             // currency
            [],                                                          // payment_method_details
            WC_Ingpsp_Helper::getOrderDescription($order_id),            // description
            $order_id,                                                   // merchant_order_id
            WC_Ingpsp_Helper::getReturnUrl(),                            // return_url
            null,                                                        // expiration
            WC_Ingpsp_Helper::getCustomerInfo($order),                   // customer
            ['plugin' => INGPSP_PLUGIN_VERSION],                         // extra information
            WC_Ingpsp_Helper::getWebhookUrl($this)                       // webhook_url
        );
        
        update_post_meta($order_id, 'ing_order_id', $ingOrder->getId());

        if ($ingOrder->status()->isError()) {
            wc_add_notice(__('There was a problem processing your transaction.'), 'error');
            return [
                'result' => 'failure'
            ];
        }

        WC_Ingpsp_Helper::reduceStock($order);

        return [
            'result' => 'success',
            'redirect' => $ingOrder->firstTransactionPaymentUrl()->toString()
        ];
    }
}

