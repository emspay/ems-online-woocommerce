<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_Ideal extends WC_Emspay_Gateway
{
    /**
     * WC_Emspay_Ideal constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_ideal';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = __('iDEAL - EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('iDEAL - EMS Online', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $idealIssuerId = sanitize_text_field($_POST["ems_ideal_issuer_id"]);

        if (empty($idealIssuerId)) {
            wc_add_notice(__('Payment Error: You must choose an iDEAL Bank!', WC_Emspay_Helper::DOMAIN), 'error');
            return ['result' => 'failure'];
        }

        $order = new WC_Order($order_id);

        $emsOrder = $this->ems->createIdealOrder(
            WC_Emspay_Helper::gerOrderTotalInCents($order),          // Amount in cents
            WC_Emspay_Helper::getCurrency(),                         // currency
            $idealIssuerId,                                          // ideal_issuer_id
            WC_Emspay_Helper::getOrderDescription($order_id),        // order description
            $order_id,                                               // merchantOrderId
            WC_Emspay_Helper::getReturnUrl(),                        // returnUrl
            null,                                                    // expiration
            WC_Emspay_Helper::getCustomerInfo($order),               // customer
            ['plugin' => EMSPAY_PLUGIN_VERSION],                     // extra information
            WC_Emspay_Helper::getWebhookUrl($this)                   // webhook_url
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
            'redirect' => $emsOrder->firstTransactionPaymentUrl()->toString(),
        ];
    }

    /**
     * @return null|void
     */
    public function payment_fields()
    {
        if (!$this->has_fields) {
            return null;
        }
        echo '<select name="ems_ideal_issuer_id">';
        echo '<option value="">'.__('Choose your bank:', WC_Emspay_Helper::DOMAIN).'</option>';
        foreach ($this->ems->getIdealIssuers()->toArray() AS $issuer) {
            echo '<option value="'.$issuer['id'].'">'.htmlspecialchars($issuer['name']).'</option>';
        }
        echo '</select>';
    }
}
