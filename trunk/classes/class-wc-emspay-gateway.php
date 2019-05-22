<?php

if (!defined('ABSPATH')) {
    exit;
}

use \GingerPayments\Payment\Ginger;

class WC_Emspay_Gateway extends WC_Payment_Gateway
{
    var $ems;

    public function __construct()
    {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');

        $settings = get_option('woocommerce_emspay_settings');
        $apiKey = ($settings['test_api_key'])?$settings['test_api_key']:$settings['api_key'];

        if ($this->id == 'emspay_afterpay' && $this->get_option('ap_test_api_key')) {
            $apiKey = $this->get_option('ap_test_api_key');
        }

        if (strlen($apiKey) > 0) {
            try {
                $this->ems = Ginger::createClient($apiKey, $settings['psp_product']);
                if ($settings['bundle_cacert'] == 'yes') {
                    $this->ems->useBundledCA();
                }
            } catch (Assert\InvalidArgumentException $exception) {
                WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
            }
        }

        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_'.$this->id, array($this, 'handle_thankyou'));
        add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'handle_callback'));
    }

    public function handle_thankyou($order_id)
    {
        WC()->cart->empty_cart();

        $ems_order_id_array = get_post_custom_values('ems_order_id', $order_id);
        if (is_array($ems_order_id_array) && !empty($ems_order_id_array[0])) {
            $emsOrder = $this->ems->getOrder($ems_order_id_array[0]);

            if ($emsOrder->status()->isProcessing()) {
                echo __(
                    "Your transaction is still being processed. You will be notified when status is updated.",
                    WC_Emspay_Helper::DOMAIN
                );
            }
        }
    }

    function admin_options()
    {
        echo '<h2>'.$this->method_title.'</h2>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function init_form_fields()
    {
        $this->form_fields = WC_Emspay_Helper::getFormFields($this->id);
    }

    public function get_icon()
    {
        return apply_filters('woocommerce_gateway_icon', WC_Emspay_Helper::getIconSource($this->id), $this->id);
    }
}
