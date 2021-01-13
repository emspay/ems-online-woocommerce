<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_KlarnaPayLater extends WC_Emspay_Gateway
{
    /**
     * WC_Emspay_KlarnaPayLater constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_klarna-pay-later';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Klarna Pay Later - EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('Klarna Pay Later - EMS Online', WC_Emspay_Helper::DOMAIN);

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
                'webhook_url' => WC_Emspay_Helper::getWebhookUrl(),
                'order_lines' => WC_Emspay_Helper::getOrderLines($order)
            ]));
        } catch (\Exception $exception) {
            wc_add_notice(sprintf(__('There was a problem processing your transaction: %s', WC_Emspay_Helper::DOMAIN), $exception->getMessage()), 'error');
            return [
                'result' => 'failure'
            ];
        }

        update_post_meta($order_id, 'ems_order_id', $emsOrder['id']);

        if ($emsOrder['status'] == 'error') {
            wc_add_notice(current($emsOrder['transactions'])['reason'], 'error');
            return [
                'result' => 'failure',
            ];
        } elseif ($emsOrder['status'] == 'cancelled') {
            wc_add_notice(
                __('Unfortunately, we can not currently accept your purchase with Klarna. Please choose another payment option to complete your order. We apologize for the inconvenience.'),
                'error'
            );
            return [
                'result' => 'failure',
                'redirect' => $order->get_cancel_order_url($order)
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

    /**
     * @param $order_id
     */
    public function handle_thankyou($order_id)
    {
        WC()->cart->empty_cart();

        echo "<p><b>".__('Your payment using Klarna is successful.', WC_Emspay_Helper::DOMAIN)."</b></p>";
        echo "<p>".__('The invoice will be sent to your email.', WC_Emspay_Helper::DOMAIN)."</p>";
    }
}
