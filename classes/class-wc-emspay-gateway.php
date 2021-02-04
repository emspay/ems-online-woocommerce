<?php

if (!defined('ABSPATH')) {
    exit;
}

use \Ginger\Ginger;

class WC_Emspay_Gateway extends WC_Payment_Gateway
{
    protected $ems;
    protected $allowed_currencies;

    public function __construct()
    {
        $this->ginger_init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->allowed_currencies = $this->get_option('allowed_currencies');

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
                            CURLOPT_CAINFO => WC_Emspay_Helper::gingerGetCaCertPath()
                        ] : []
                );
            } catch (Assert\InvalidArgumentException $exception) {
                WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
            }
        }

        if( is_admin()) {
            $this->validate_currency();
        }

        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_'.$this->id, array($this, 'ginger_handle_thankyou'));
        add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'ginger_handle_callback'));
        add_filter('woocommerce_valid_order_statuses_for_payment_complete', array($this, 'ginger_append_processing_order_post_status'));
    }

    /**
     * Function ginger_append_processing_order_post_status
     * Appended 'processing' order post status to correct status update for 'processing' or 'complemented' by WooCommerce
     *
     * @param $statuses
     * @return mixed
     */
    public function ginger_append_processing_order_post_status($statuses) {
        if(! in_array('processing', $statuses)) {
            $statuses[] = 'processing';
        }

        return $statuses;
    }

    public function ginger_handle_thankyou($order_id)
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
        if (!$this->enabled && count($this->errors)) {
            echo '<div class="inline error"><p><strong>' . __('Gateway Disabled', WC_Emspay_Helper::DOMAIN) . '</strong>: '
                . implode('<br/>', $this->errors)
                . '</p></div>';
        }

        echo '<h2>'.esc_html($this->method_title).'</h2>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function ginger_init_form_fields()
    {
        $this->form_fields = WC_Emspay_Helper::gingerGetFormFields($this->id);
    }

    public function get_icon()
    {
        return apply_filters('woocommerce_gateway_icon', WC_Emspay_Helper::gingerGetIconSource($this->id), $this->id);
    }

    /**
     * Function validate_currency
     */
    protected function validate_currency() {

        if ( ! $this->isStoreCurrencySupported() ) {
            $this->enabled= false;
            $this->update_option('enabled', false);

            $this->errors[] = sprintf(
                __( 'Current shop currency %s not supported by EMS Online.', WC_Emspay_Helper::DOMAIN ),
                get_woocommerce_currency()
            );
        }

        if ( ! $this->isGatewayCurrencySupported() ) {
            $this->enabled = false;
            $this->update_option('enabled', false);

            $this->errors[] = sprintf(
                __( 'Current shop currency %s not supported by %s.', WC_Emspay_Helper::DOMAIN ),
                get_woocommerce_currency(),
                $this->get_option('title')
            );
        }
    }

    /**
     * @return bool
     */
    protected function isStoreCurrencySupported ()
    {
        return in_array(get_woocommerce_currency(), WC_Emspay_Helper::$supportedCurrencies);
    }

    /**
     * @return bool
     */
    protected function isGatewayCurrencySupported ()
    {
        if(empty($this->allowed_currencies)) {
           return true;
        }
        return in_array(get_woocommerce_currency(), $this->allowed_currencies);
    }
}
