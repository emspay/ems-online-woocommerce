<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ingpsp_Banktransfer extends WC_Ingpsp_Gateway
{
    /**
     * WC_ingpsp_banktransfer constructor.
     */
    public function __construct()
    {
        $this->id = 'ingpsp_banktransfer';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Banktransfer - ING PSP', WC_Ingpsp_Helper::DOMAIN);
        $this->method_description = __('Banktransfer - ING PSP', WC_Ingpsp_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $ingOrder = $this->ing->createSepaOrder(
            WC_Ingpsp_Helper::gerOrderTotalInCents($order),          // Amount in cents
            WC_Ingpsp_Helper::getCurrency(),                         // Currency
            [],                                                      // Payment method details
            WC_Ingpsp_Helper::getOrderDescription($order_id),        // Description
            $order_id,                                               // Merchant id
            WC_Ingpsp_Helper::getReturnUrl(),                        // Return url,
            null,                                                    // expiration
            WC_Ingpsp_Helper::getCustomerInfo($order),               // customer
            ['plugin' => INGPSP_PLUGIN_VERSION],                     // extra information
            WC_Ingpsp_Helper::getWebhookUrl($this)                   // webhook_url
        );

        $bank_reference = $ingOrder->Transactions()->current()->paymentMethodDetails()->reference()->toString();

        update_post_meta($order_id, 'bank_reference', $bank_reference);
        update_post_meta($order_id, 'ing_order_id', $ingOrder->getId());

        $order->update_status('on-hold', __('Awaiting Bank-Transfer Payment', WC_Ingpsp_Helper::DOMAIN));

        WC_Ingpsp_Helper::reduceStock($order);

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }

    /**
     * @param $order_id
     */
    public function handle_thankyou($order_id)
    {
        WC()->cart->empty_cart();

        $reference = get_post_custom_values('bank_reference', $order_id);

        echo __("Please use the following payment information:", WC_Ingpsp_Helper::DOMAIN);
        echo "<br/>";
        echo __("Bank Reference: ".$reference[0], WC_Ingpsp_Helper::DOMAIN);
        echo "<br/>";
        echo __("IBAN: NL13INGB0005300060", WC_Ingpsp_Helper::DOMAIN);
        echo "<br/>";
        echo __("BIC: INGBNL2A", WC_Ingpsp_Helper::DOMAIN);
        echo "<br/>";
        echo __("Account Holder: ING Bank N.V. PSP", WC_Ingpsp_Helper::DOMAIN);
        echo "<br/>";
        echo __("Residence: Amsterdam", WC_Ingpsp_Helper::DOMAIN);
        echo "<br/><br/>";
    }
}
