<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_Klarna extends WC_Emspay_Gateway
{
    /**
     * WC_Emspay_Creditcard constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_klarna';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = __('Klarna - EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('Klarna - EMS Online', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $emsOrder = $this->ems->createKlarnaOrder(
            WC_Emspay_Helper::gerOrderTotalInCents($order),              // Amount in cents
            WC_Emspay_Helper::getCurrency(),                             // currency
            WC_Emspay_Helper::getOrderDescription($order_id),            // description
            $order_id,                                                   // merchant_order_id
            WC_Emspay_Helper::getReturnUrl(),                            // return_url
            null,                                                        // expiration
            WC_Emspay_Helper::getCustomerInfo($order),                   // customer
            ['plugin' => EMSPAY_PLUGIN_VERSION],                         // extra information
            WC_Emspay_Helper::getWebhookUrl($this),                      // webhook_url
            WC_Emspay_Helper::getOrderLines($order)                      // order_lines
        );

        update_post_meta($order_id, 'ems_order_id', $emsOrder->getId());

        if ($emsOrder->status()->isError()) {
            wc_add_notice($emsOrder->transactions()->current()->reason()->toString(), 'error');
            return [
                'result' => 'failure',
            ];
        } elseif ($emsOrder->status()->isCancelled()) {
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

        $order->update_status('on-hold', __('Klarna payment request sent.', WC_Emspay_Helper::DOMAIN));

        WC_Emspay_Helper::reduceStock($order);

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

        echo "<p><b>".__('Your payment using Klarna is successful.', WC_Emspay_Helper::DOMAIN)."</b></p>";
        echo "<p>".__('The invoice will be sent to your email.', WC_Emspay_Helper::DOMAIN)."</p>";
    }

    /**
     * @return null|void
     */
    public function payment_fields()
    {
        if (!$this->has_fields) {
            return null;
        }

        echo '<fieldset><legend>'.__('Additional Information', WC_Emspay_Helper::DOMAIN).'</legend >';

        woocommerce_form_field('gender', array(
            'type' => 'select',
            'class' => array('input-text'),
            'label' => __('Gender:', WC_Emspay_Helper::DOMAIN),
            'options' => array(
                '' => '',
                'male' => __('Male', WC_Emspay_Helper::DOMAIN),
                'female' => __('Female', WC_Emspay_Helper::DOMAIN),
            ),
            'required' => true
        ));

        woocommerce_form_field('dob', array(
            'type' => 'text',
            'class' => array('input-text'),
            'label' => __('Date of birth:', WC_Emspay_Helper::DOMAIN),
            'description' => __('Birth date format: DD - MM - YYYY, eg: 31-12-1980', WC_Emspay_Helper::DOMAIN),
            'required' => true
        ));

        echo "</fieldset>";

    }
}
