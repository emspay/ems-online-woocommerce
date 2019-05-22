<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ingpsp_Ideal extends WC_Ingpsp_Gateway
{
    /**
     * WC_Ingpsp_Ideal constructor.
     */
    public function __construct()
    {
        $this->id = 'ingpsp_ideal';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = __('iDEAL - ING PSP', WC_Ingpsp_Helper::DOMAIN);
        $this->method_description = __('iDEAL - ING PSP', WC_Ingpsp_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $idealIssuerId = sanitize_text_field($_POST["ing_ideal_issuer_id"]);

        if (empty($idealIssuerId)) {
            wc_add_notice(__('Payment Error: You must choose an iDEAL Bank!', WC_Ingpsp_Helper::DOMAIN), 'error');
            return ['result' => 'failure'];
        }

        $order = new WC_Order($order_id);

        $ingOrder = $this->ing->createIdealOrder(
            WC_Ingpsp_Helper::gerOrderTotalInCents($order),          // Amount in cents
            WC_Ingpsp_Helper::getCurrency(),                         // currency
            $idealIssuerId,                                          // ideal_issuer_id
            WC_Ingpsp_Helper::getOrderDescription($order_id),        // order description
            $order_id,                                               // merchantOrderId
            WC_Ingpsp_Helper::getReturnUrl(),                        // returnUrl
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
            'redirect' => $ingOrder->firstTransactionPaymentUrl()->toString(),
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
        echo '<select name="ing_ideal_issuer_id">';
        echo '<option value="">'.__('Choose your bank:', WC_Ingpsp_Helper::DOMAIN).'</option>';
        foreach ($this->ing->getIdealIssuers()->toArray() AS $issuer) {
            echo '<option value="'.$issuer['id'].'">'.htmlspecialchars($issuer['name']).'</option>';
        }
        echo '</select>';
    }
}
