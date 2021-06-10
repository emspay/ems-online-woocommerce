<?php

if (!defined('ABSPATH')) {
    exit;
}

use \Ginger\Ginger;

class WC_Emspay_Callback extends WC_Emspay_Gateway
{
    public function __construct()
    {
        $this->id = 'emspay';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('EMS Online', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    public function ginger_init_form_fields()
    {
        $this->form_fields = [
            'api_key' => [
                'title' => __('API key', WC_Emspay_Helper::DOMAIN),
                'type' => 'text',
                'description' => __('API key provided by EMS', WC_Emspay_Helper::DOMAIN),
            ],
            'test_api_key' => [
                'title' => __('Test API key', WC_Emspay_Helper::DOMAIN),
                'type' => 'text',
                'description' => __('Test API key for testing implementation of Klarna.', WC_Emspay_Helper::DOMAIN),
            ],
            'debug_klarna_ip' => [
                'title' => __('Klarna Debug IP', WC_Emspay_Helper::DOMAIN),
                'type' => 'text',
                'description' => __('IP address for testing Klarna. If empty, visible for all. If filled, only visible for specified IP addresses. (Example: 127.0.0.1, 255.255.255.255)', WC_Emspay_Helper::DOMAIN),
            ],
            'failed_redirect' => [
                'title' => __('Failed payment page', WC_Emspay_Helper::DOMAIN),
                'description' => __(
                    'Page where user is redirected after payment has failed.',
                    WC_Emspay_Helper::DOMAIN
                ),
                'type' => 'select',
                'options' => [
                    'checkout' => __('Checkout Page', WC_Emspay_Helper::DOMAIN),
                    'cart' => __('Shopping Cart', WC_Emspay_Helper::DOMAIN)
                ],
                'default' => 'checkout',
                'desc_tip' => true
            ],
            'bundle_cacert' => [
                'title' => __('cURL CA bundle', WC_Emspay_Helper::DOMAIN),
                'label' => __('Use cURL CA bundle', WC_Emspay_Helper::DOMAIN),
                'description' => __(
                    'Resolves issue when curl.cacert path is not set in PHP.ini',
                    WC_Emspay_Helper::DOMAIN
                ),
                'type' => 'checkbox',
                'desc_tip' => true
            ]
        ];
    }

    public function ginger_handle_callback()
    {
        if (!empty($ems_order_id = sanitize_text_field($_GET['order_id']))) {
            $type = "return";
        } else {
            $type = "webhook";
            $input = json_decode(file_get_contents("php://input"), true);

            if (!in_array($input['event'], array("status_changed"))) {
                die("Only work to do if the status changed");
            }
            $ems_order_id = $input['order_id'];
        }

        // we potentially have 3 different API keys we can fetch the order with
        $settings = get_option('woocommerce_emspay_settings');
        $ap_settings = get_option('woocommerce_emspay_afterpay_settings');
        $cacert_path = WC_Emspay_Helper::gingerGetCaCertPath();

        $success_get = false;
        // first try with standard API key
        try {
            $emsOrder = $this->ems->getOrder($ems_order_id);
            $success_get = true;
        } catch (Exception $exception) {
        }

        // second try with api key from Klarna
        if (!$success_get) {
            if ($settings['test_api_key']) {

                $this->ems = Ginger::createClient(
                    WC_Emspay_Helper::GINGER_ENDPOINT,
                    $settings['test_api_key'],
                    ($settings['bundle_cacert'] == 'yes') ?
                        [
                            CURLOPT_CAINFO => $cacert_path
                        ] : []
                );

                try {
                    $emsOrder = $this->ems->getOrder($ems_order_id);
                    $success_get = true;
                } catch (Exception $exception) {
                }
            }
        }

        // third try with api key from Afterpay
        if (!$success_get) {
            if ($ap_settings['ap_test_api_key']) {

                $this->ems = Ginger::createClient(
                    WC_Emspay_Helper::GINGER_ENDPOINT,
                    $ap_settings['ap_test_api_key'],
                    ($settings['bundle_cacert'] == 'yes') ?
                        [
                            CURLOPT_CAINFO => $cacert_path
                        ] : []
                );

                try {
                    $emsOrder = $this->ems->getOrder($ems_order_id);
                    $success_get = true;
                } catch (Exception $exception) {
                }
            }
        }

        if (!$success_get) {
            die("COULD NOT GET ORDER");
        }

        $order = new WC_Order($emsOrder['merchant_order_id']);

        if ($type == "webhook") {
            $ems_order_id_meta = get_post_meta($emsOrder['merchant_order_id'], 'ems_order_id', true);
            if(! empty($ems_order_id_meta) and $emsOrder['id'] !== $ems_order_id_meta) {
                exit;
            }

            if ($emsOrder['status'] == 'completed') {
                $woo_version = get_option('woocommerce_version', 'Unknown');
                if (version_compare($woo_version, '2.2.0', '>=')) {
                    $order->payment_complete($ems_order_id);
                } else {
                    $order->payment_complete();
                }
            } elseif (isset($emsOrder['transactions']['flags']['has-captures'])){
                if ($order->get_status() == 'processing')
                    $order->update_status('shipped', 'Order updated to shipped, transactions was captured', false);
            } else {
                $order->update_status($this->ginger_get_store_status($emsOrder['status']));
            }
            exit;
        }

        if ($emsOrder['status'] == 'completed' || $emsOrder['status'] == 'processing') {
            header("Location: ".$this->get_return_url($order));
            exit;
        } else {
            wc_add_notice(__('There was a problem processing your transaction.', WC_Emspay_Helper::DOMAIN), 'error');
            if ($this->get_option('failed_redirect') == 'cart') {
                $url = $order->get_cancel_order_url();
            } else {
                $url = $order->get_checkout_payment_url();
            }
            header("Location: ".str_replace("&amp;", "&", $url));
            exit;
        }
    }

    /**
     * Function ginger_get_store_status
     *
     * @param $ems_order_status
     * @return string
     */
    public function ginger_get_store_status($ems_order_status) {
        $maps_statuses = [
            'new' => 'pending',
            'processing' => 'pending',
            'error' => 'failed',
            'expired' => 'cancelled',
            'cancelled' => 'cancelled',
            'see-transactions' => 'on-hold'
        ];
        return $maps_statuses[$ems_order_status];
    }
}
