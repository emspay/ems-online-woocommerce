<?php

/**
 * Plugin Name: EMS Online
 * Plugin URI: https://emspay.nl/
 * Description: EMS Pay WooCommerce plugin
 * Version: 1.0.4
 * Author: Ginger Payments
 * Author URI: https://www.gingerpayments.com/
 * License: The MIT License (MIT)
 * Text Domain: emspay
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define EMS Online plugin version
 */
define('EMSPAY_PLUGIN_VERSION', 'WooCommerce v1.0.0');
define('EMSPAY_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', 'woocommerce_emspay_init', 0);

spl_autoload_register(function ($class) {
    $file = str_replace('_', '-', strtolower($class));
    $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/classes/class-'.$file.'.php';

    if (is_readable($filepath) && is_file($filepath)) {
        require_once($filepath);
    }
});

require_once(untrailingslashit(plugin_dir_path(__FILE__)).'/ems-php/vendor/autoload.php');

function woocommerce_emspay_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Just an alias for API callback action
    class woocommerce_emspay extends WC_Emspay_Callback
    {
    }

    function woocommerce_add_emspay($methods)
    {
        $settings = get_option('woocommerce_emspay_settings');
        $allowed_products = [];
        $apiTestMode = false;

        if (strlen($settings['api_key']) > 0) {
            try {
                $ginger = \GingerPayments\Payment\Ginger::createClient($settings['api_key']);
                if ($settings['bundle_cacert'] == 'yes') {
                    $ginger->useBundledCA();
                }
            } catch (Exception $exception) {
                WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
            }
        }

        $methods = [
            'WC_Emspay_Callback',
            'WC_Emspay_Ideal',
            'WC_Emspay_Banktransfer',
            'WC_Emspay_Bancontact',
            'WC_Emspay_Creditcard',
            'WC_Emspay_PayPal',
            'WC_Emspay_Klarna',
            'WC_Emspay_Sofort',
            'WC_Emspay_Payconiq',
            'WC_Emspay_AfterPay',
            'WC_Emspay_ApplePay',
            'WC_Emspay_Paynow',
        ];

        return $methods;
    }

    /**
     * Check if Klarna payment method is limited to specific set of IPs.
     *
     * @param $gateways
     * @return mixed
     */
    function klarna_enabled_ip($gateways)
    {
        $settings = get_option('woocommerce_emspay_settings');
        $ems_klarna_ip_list = $settings['debug_klarna_ip'];

        if (strlen($ems_klarna_ip_list) > 0) {
            $ip_whitelist = array_map('trim', explode(",", $ems_klarna_ip_list));

            if (!in_array(WC_Geolocation::get_ip_address(), $ip_whitelist)) {
                unset($gateways['emspay_klarna']);
            }
        }

        return $gateways;
    }

    add_filter('woocommerce_available_payment_gateways', 'klarna_enabled_ip');
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_emspay');
    add_action('woocommerce_api_callback', array(new woocommerce_emspay(), 'handle_callback'));

    function register_shipped_order_status()
    {
        register_post_status('wc-shipped', array(
            'label' => 'Shipped',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>')
        ));
    }

    add_action('init', 'register_shipped_order_status');

    /**
     * @param array $order_statuses
     * @return array
     */
    function add_shipped_to_order_statuses(array $order_statuses)
    {
        $new_order_statuses = array();
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-processing' === $key) {
                $new_order_statuses['wc-shipped'] = 'Shipped';
            }
        }
        return $new_order_statuses;
    }

    add_filter('wc_order_statuses', 'add_shipped_to_order_statuses');

    add_action('woocommerce_update_order', 'ship_an_order');

    load_plugin_textdomain(WC_Emspay_Helper::DOMAIN, false, basename(dirname(__FILE__)).'/languages');

    /**
     * Support for Klarna and Afterpay order shipped state
     *
     * @param int $order_id
     */
    function ship_an_order($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order && $order->get_status() == 'shipped' && in_array($order->get_payment_method(), array('emspay_klarna', 'emspay_afterpay'))) {

            $settings = get_option('woocommerce_emspay_settings');
            $ap_settings = get_option('woocommerce_emspay_afterpay_settings');

            switch ($order->get_payment_method()) {
                case 'emspay_klarna':
                    $apiKey = ($settings['test_api_key'])?$settings['test_api_key']:$settings['api_key'];
                    break;
                case 'emspay_afterpay':
                    $apiKey = ($ap_settings['ap_test_api_key'])?$ap_settings['ap_test_api_key']:$settings['api_key'];
                    break;
            }
            
            if (strlen($apiKey) > 0) {
                try {
                    $ginger = \GingerPayments\Payment\Ginger::createClient($apiKey);
                    if ($settings['bundle_cacert'] == 'yes') {
                        $ginger->useBundledCA();
                    }
                } catch (Exception $exception) {
                    WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
                }
            }

            try {
                $id = get_post_meta($order_id, 'ems_order_id', true);
                $ginger->setOrderCapturedStatus($ginger->getOrder($id));
            } catch (\Exception $exception) {
                WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
            }
        }
    }

    /**
     * Custom text on the receipt page.
     *
     * @param string $text
     * @param WC_Order $order
     * @return string
     */
    function emspay_order_received_text($text, $order)
    {
        return WC_Emspay_Helper::getOrderDescription($order->get_id());
    }

    /**
     * Filter out EMS Online AfterPay method if not in allowed countries.
     *
     * @param array $gateways
     * @return mixed
     */
    function afterpay_filter_gateways($gateways)
    {
        $settings = get_option('woocommerce_emspay_afterpay_settings');
        $ap_ip = $settings['ap_debug_ip'];

        if (strlen($ap_ip) > 0) {
            $ip_whitelist = array_map('trim', explode(",", $ap_ip));
            if (!in_array(WC_Geolocation::get_ip_address(), $ip_whitelist)) {
                unset($gateways['emspay_afterpay']);
            }
        } else if (isset(WC()->customer->billing['country']) && !in_array(WC()->customer->billing['country'], WC_Emspay_Helper::$afterPayCountries)) {
            unset($gateways['emspay_afterpay']);
        }

        return $gateways;
    }

    add_filter('woocommerce_available_payment_gateways', 'afterpay_filter_gateways', 10);
    add_filter('woocommerce_thankyou_order_received_text', 'emspay_order_received_text', 10, 2);
}
