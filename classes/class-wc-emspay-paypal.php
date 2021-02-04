<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_PayPal extends WC_Emspay_Gateway
{
    /**
     * WC_Emspay_PayPal constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_paypal';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('PayPal - EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('PayPal - EMS Online', WC_Emspay_Helper::DOMAIN);

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
                'amount' => WC_Emspay_Helper::gingerGerOrderTotalInCents($order),
                'currency' => WC_Emspay_Helper::gingerGetCurrency(),
                'transactions' => [
                    [
                        'payment_method' => str_replace('emspay_', '', $this->id)
                    ]
                ],
                'merchant_order_id' => (string) $order_id,
                'description' => WC_Emspay_Helper::gingerGetOrderDescription($order_id),
                'return_url' => WC_Emspay_Helper::gingergetReturnUrl(),
                'customer' => WC_Emspay_Helper::gingerGetCustomerInfo($order),
                'extra' => ['plugin' => EMSPAY_PLUGIN_VERSION],
                'webhook_url' => WC_Emspay_Helper::gingerGetWebhookUrl()
            ]));
        } catch (\Exception $exception) {
            wc_add_notice(sprintf(__('There was a problem processing your transaction: %s', WC_Emspay_Helper::DOMAIN), $exception->getMessage()), 'error');
            return [
                'result' => 'failure'
            ];
        }

        update_post_meta($order_id, 'ems_order_id', $emsOrder['id']);

        if ($emsOrder['status'] == 'error') {
            wc_add_notice(__('There was a problem processing your transaction.', WC_Emspay_Helper::DOMAIN), 'error');
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
