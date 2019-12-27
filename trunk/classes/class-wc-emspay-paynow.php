<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_PayNow extends WC_Emspay_Gateway
{
    /**
     * WC_Emspay_PayNow constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_pay-now';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Pay now - EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('Pay now - EMS Online', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $emsOrder = $this->ems->createOrder([
            'amount' => (int) WC_Emspay_Helper::gerOrderTotalInCents($order),
            'currency' => (string) WC_Emspay_Helper::getCurrency(),
            'merchant_order_id' => (string) $order_id,
            'description' => (string) WC_Emspay_Helper::getOrderDescription($order_id),
            'return_url' => (string) WC_Emspay_Helper::getReturnUrl(),
            'customer' => (array) WC_Emspay_Helper::getCustomerInfo($order),
			'extra' => ['plugin' => EMSPAY_PLUGIN_VERSION],
            'webhook_url' => (string) WC_Emspay_Helper::getWebhookUrl($this)
        ]);
        
        update_post_meta($order_id, 'ems_order_id', $emsOrder['id']);

        if ($emsOrder['status'] == 'error') {
            wc_add_notice(__('There was a problem processing your transaction.', WC_Emspay_Helper::DOMAIN), 'error');
            return [
                'result' => 'failure'
            ];
        }

        return [
            'result' => 'success',
            'redirect' => $emsOrder['order_url']
        ];
    }
}
