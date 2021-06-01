<?php

if (!defined('ABSPATH')) {
    exit;
}

use \Ginger\Ginger;

class WC_Emspay_Gateway extends WC_Payment_Gateway
{
    protected $ems;
    protected $emspay_settings;

    public function __construct()
    {
        global $current_tab;
        global $current_section;

        $this->ginger_init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->emspay_settings = get_option('woocommerce_emspay_settings');

        $apiKey = ($this->emspay_settings['test_api_key'])?$this->emspay_settings['test_api_key']:$this->emspay_settings['api_key'];

        if ($this->id == 'emspay_afterpay' && $this->get_option('ap_test_api_key')) {
            $apiKey = $this->get_option('ap_test_api_key');
        }

        if (strlen($apiKey) > 0) {
            try {
                $this->ems = Ginger::createClient(
                    WC_Emspay_Helper::GINGER_ENDPOINT,
                    $apiKey,
                    ($this->emspay_settings['bundle_cacert'] == 'yes') ?
                        [
                            CURLOPT_CAINFO => WC_Emspay_Helper::gingerGetCaCertPath()
                        ] : []
                );
            } catch (Assert\InvalidArgumentException $exception) {
                WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
            }
        }

        // Check if payment method supports store currency while saving payment method settings in the admin
        if( is_admin() ) {
            if( $current_tab == 'checkout' and $current_section == $this->id ) {
                if($this->id !== 'emspay_pay-now') {
                    $this->ginger_validate_currency();
                }
            }
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
     * Function ginger_validate_currency
     */
    protected function ginger_validate_currency() {

        $current_currency = get_woocommerce_currency();
        if ( ! $this->gingerIsGatewayCurrencySupported() ) {
            $this->enabled = false;
            $this->update_option('enabled', false);

            $this->errors[] = sprintf(
                __( 'Current shop currency %s not supported by %s.', WC_Emspay_Helper::DOMAIN ),
                $current_currency,
                $this->get_option('title')
            );
        }
    }

    /**
     * @return bool
     */
    protected function gingerIsGatewayCurrencySupported () {
        return in_array(get_woocommerce_currency(), $this->paymentSuportedCurrencies());
    }

    /**
     * Function paymentSuportedCurrencies
     *
     * @return mixed
     */
    protected function paymentSuportedCurrencies() {
        $currentMethod = strtr($this->id, ['emspay_' => '']);
        $payment_methods_currencies = $this->ems->send('GET', '/merchants/self/projects/self/currencies');
        return $payment_methods_currencies['payment_methods'][$currentMethod]['currencies'];
    }
}
