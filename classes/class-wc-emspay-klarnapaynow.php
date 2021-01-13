<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_KlarnaPayNow extends WC_Emspay_Gateway
{
    /**
     * WC_Emspay_KlarnaPayNow constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_klarna-pay-now';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('EMS Online - Klarna Pay Now', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('EMS Online - Klarna Pay Now', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        try {
            $emsOrder = $this->ems->createOrder(array_filter([
                'amount' => WC_Emspay_Helper::gerOrderTotalInCents($order),
                'currency' => WC_Emspay_Helper::getCurrency(),
                'transactions' => [
                    [
                        'payment_method' => str_replace('emspay_', '', $this->id)
                    ]
                ],
                'merchant_order_id' => (string) $order_id,
                'description' => WC_Emspay_Helper::getOrderDescription($order_id),
                'return_url' => WC_Emspay_Helper::getReturnUrl(),
                'customer' => WC_Emspay_Helper::getCustomerInfo($order),
                'extra' => ['plugin' => EMSPAY_PLUGIN_VERSION],
                'webhook_url' => WC_Emspay_Helper::getWebhookUrl()
            ]));
        } catch (\Exception $exception) {
            wc_add_notice(sprintf(__('There was a problem processing your transaction: %s', WC_Emspay_Helper::DOMAIN), $exception->getMessage()), 'error');
            return [
                'result' => 'failure'
            ];
        }

        update_post_meta($order_id, 'ems_order_id', $emsOrder['id']);

        if ($emsOrder['status'] == 'error') {
            wc_add_notice(__('There was a problem processing your transaction.'), 'error');
            return [
                'result' => 'failure'
            ];
        }

        $pay_url = array_key_exists(0, $emsOrder['transactions'])
            ? $emsOrder['transactions'][0]['payment_url']
            : null;

        return [
            'result' => 'success',
            'redirect' => $pay_url
        ];
    }
}

