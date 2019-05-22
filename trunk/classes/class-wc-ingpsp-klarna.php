<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ingpsp_Klarna extends WC_Ingpsp_Gateway
{
    /**
     * WC_Ingpsp_Creditcard constructor.
     */
    public function __construct()
    {
        $this->id = 'ingpsp_klarna';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = __('Klarna - ING PSP', WC_Ingpsp_Helper::DOMAIN);
        $this->method_description = __('Klarna - ING PSP', WC_Ingpsp_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $ingOrder = $this->ing->createKlarnaOrder(
            WC_Ingpsp_Helper::gerOrderTotalInCents($order),              // Amount in cents
            WC_Ingpsp_Helper::getCurrency(),                             // currency
            WC_Ingpsp_Helper::getOrderDescription($order_id),            // description
            $order_id,                                                   // merchant_order_id
            WC_Ingpsp_Helper::getReturnUrl(),                            // return_url
            null,                                                        // expiration
            WC_Ingpsp_Helper::getCustomerInfo($order),                   // customer
            ['plugin' => INGPSP_PLUGIN_VERSION],                         // extra information
            WC_Ingpsp_Helper::getWebhookUrl($this),                      // webhook_url
            WC_Ingpsp_Helper::getOrderLines($order)                      // order_lines
        );

        update_post_meta($order_id, 'ing_order_id', $ingOrder->getId());

        if ($ingOrder->status()->isError()) {
            wc_add_notice($ingOrder->transactions()->current()->reason()->toString(), 'error');
            return [
                'result' => 'failure',
            ];
        } elseif ($ingOrder->status()->isCancelled()) {
            wc_add_notice(
                __('Unfortunately, we can not currently accept your purchase with Klarna. 
                    Please choose another payment option to complete your order. We apologize for the inconvenience.'),
                'error'
            );
            return [
                'result' => 'failure',
                'redirect' => $order->get_cancel_order_url($order)
            ];
        }

        $order->update_status('on-hold', __('Klarna payment request sent.', WC_Ingpsp_Helper::DOMAIN));

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

        echo "<p><b>".__('Your payment using Klarna is successful.', WC_Ingpsp_Helper::DOMAIN)."</b></p>";
        echo "<p>".__('The invoice will be sent to your email.', WC_Ingpsp_Helper::DOMAIN)."</p>";
    }

    /**
     * @return null|void
     */
    public function payment_fields()
    {
        if (!$this->has_fields) {
            return null;
        }

        echo '<fieldset><legend>'.__('Additional Information', WC_Ingpsp_Helper::DOMAIN).'</legend >';

        woocommerce_form_field('gender', array(
            'type' => 'select',
            'class' => array('input-text'),
            'label' => __('Gender:', WC_Ingpsp_Helper::DOMAIN),
            'options' => array(
                '' => '',
                'male' => __('Male', WC_Ingpsp_Helper::DOMAIN),
                'female' => __('Female', WC_Ingpsp_Helper::DOMAIN),
            ),
            'required' => true
        ));

        woocommerce_form_field('dob', array(
            'type' => 'text',
            'class' => array('input-text'),
            'label' => __('Date of birth:', WC_Ingpsp_Helper::DOMAIN),
            'description' => __('Birth date format: DD - MM - YYYY, eg: 31-12-1980', WC_Ingpsp_Helper::DOMAIN),
            'required' => true
        ));

        echo "</fieldset>";

    }
}
