<?php

if (!defined('ABSPATH')) {
    exit;
}

use \Ginger\Ginger;

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
                $this->ems = Ginger::createClient(
                    WC_Emspay_Helper::GINGER_ENDPOINT,
                    $apiKey,
                    ($settings['bundle_cacert'] == 'yes') ?
                        [
                            CURLOPT_CAINFO => WC_Emspay_Helper::getCaCertPath()
                        ] : []
                );
            } catch (Assert\InvalidArgumentException $exception) {
                WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
            }
        }

        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_'.$this->id, array($this, 'handle_thankyou'));
        add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'handle_callback'));
        add_filter('woocommerce_valid_order_statuses_for_payment_complete', array($this, 'append_processing_order_post_status'));
    }

    /**
     * Function append_processing_order_post_status
     * Appended 'processing' order post status to correct status update for 'processing' or 'complemented' by WooCommerce
     *
     * @param $statuses
     * @return mixed
     */
    public function append_processing_order_post_status($statuses) {
        if(! in_array('processing', $statuses)) {
            $statuses[] = 'processing';
        }

        return $statuses;
    }

    public function handle_thankyou($order_id)
    {
        WC()->cart->empty_cart();

        $ems_order_id_array = get_post_custom_values('ems_order_id', $order_id);

        if (is_array($ems_order_id_array) && !empty($ems_order_id_array[0])) {
            $emsOrder = $this->ems->getOrder($ems_order_id_array[0]);

            if ($emsOrder['status'] == 'processing') {
                echo esc_html__(
                    "Your transaction is still being processed. You will be notified when status is updated.",
                    WC_Emspay_Helper::DOMAIN
                );
            }
        }
    }

    function admin_options()
    {
        echo '<h2>'.esc_html($this->method_title).'</h2>';
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
