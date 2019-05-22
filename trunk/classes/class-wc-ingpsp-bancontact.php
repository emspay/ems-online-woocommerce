<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ingpsp_Bancontact extends WC_Ingpsp_Gateway
{
    /**
     * WC_ingpsp_bancontact constructor.
     */
    public function __construct()
    {
        $this->id = 'ingpsp_bancontact';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Bancontact - ING PSP', WC_Ingpsp_Helper::DOMAIN);
        $this->method_description = __('Bancontact - ING PSP', WC_Ingpsp_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $ingOrder = $this->ing->createBancontactOrder(
            WC_Ingpsp_Helper::gerOrderTotalInCents($order),          // Amount in cents
            WC_Ingpsp_Helper::getCurrency(),                         // Currency
            WC_Ingpsp_Helper::getOrderDescription($order_id),        // Description
            $order_id,                                               // Merchant id
            WC_Ingpsp_Helper::getReturnUrl(),                        // Return url
            null,                                                    // expiration
            WC_Ingpsp_Helper::getCustomerInfo($order),               // customer
            ['plugin' => INGPSP_PLUGIN_VERSION],                     // extra information
            WC_Ingpsp_Helper::getWebhookUrl($this)                   // webhook_url
        );

        update_post_meta($order_id, 'ing_order_id', $ingOrder->getId());

        if ($ingOrder->status()->isError()) {
            wc_add_notice(__('There was a problem processing your transaction.', WC_Ingpsp_Helper::DOMAIN), 'error');
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
