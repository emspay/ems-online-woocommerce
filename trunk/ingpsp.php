<?php

/**
 * Plugin Name: ING PSP
 * Plugin URI: https://www.ing.nl/
 * Description: ING WooCommerce plugin for ING Kassa Compleet and ING ePay markets.
 * Version: 1.3.9
 * Author: Ginger Payments
 * Author URI: https://www.gingerpayments.com/
 * License: The MIT License (MIT)
 * Text Domain: ingpsp
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define ING PSP plugin version
 */
define('INGPSP_PLUGIN_VERSION', 'WooCommerce v1.3.9');

add_action('plugins_loaded', 'woocommerce_ingpsp_init', 0);

spl_autoload_register(function ($class) {
    $file = str_replace('_', '-', strtolower($class));
    $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/classes/class-'.$file.'.php';

    if (is_readable($filepath) && is_file($filepath)) {
        require_once($filepath);
    }
});

require_once(untrailingslashit(plugin_dir_path(__FILE__)).'/ing-php/vendor/autoload.php');

function woocommerce_ingpsp_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Just an alias for API callback action
    class woocommerce_ingpsp extends WC_Ingpsp_Callback
    {
    }

    function woocommerce_add_ingpsp($methods)
    {
        $settings = get_option('woocommerce_ingpsp_settings');
        $allowed_products = [];
        $apiTestMode = false;

        if (strlen($settings['api_key']) > 0) {
            try {
                $ginger = \GingerPayments\Payment\Ginger::createClient($settings['api_key'], $settings['psp_product']);
                if ($settings['bundle_cacert'] == 'yes') {
                    $ginger->useBundledCA();
                }
                $apiTestMode = $ginger->isInTestMode();
                if (!$apiTestMode) {
                    $allowed_products = $ginger->getAllowedProducts();
                }
            } catch (Exception $exception) {
                WC_Admin_Notices::add_custom_notice('ingpsp-error', $exception->getMessage());
            }
        }

        $methods[] = 'WC_Ingpsp_Callback';

        if (in_array('ideal', $allowed_products) || $apiTestMode) {
            $methods[] = 'WC_Ingpsp_Ideal';
        }
        if (in_array('banktransfer', $allowed_products) || $apiTestMode) {
            $methods[] = 'WC_Ingpsp_Banktransfer';
        }
        if (in_array('bancontact', $allowed_products)) {
            $methods[] = 'WC_Ingpsp_Bancontact';
        }
        if (in_array('cashondelivery', $allowed_products) || $apiTestMode) {
            $methods[] = 'WC_Ingpsp_Cashondelivery';
        }
        if (in_array('creditcard', $allowed_products)) {
            $methods[] = 'WC_Ingpsp_Creditcard';
        }
        if (in_array('paypal', $allowed_products)) {
            $methods[] = 'WC_Ingpsp_PayPal';
        }
        if (in_array('homepay', $allowed_products)) {
            $methods[] = 'WC_Ingpsp_HomePay';
        }
        if (in_array('klarna', $allowed_products) || $apiTestMode) {
            $methods[] = 'WC_Ingpsp_Klarna';
        }
        if (in_array('sofort', $allowed_products) || $apiTestMode) {
            $methods[] = 'WC_Ingpsp_Sofort';
        }
        if (in_array('payconiq', $allowed_products)) {
            $methods[] = 'WC_Ingpsp_Payconiq';
        }
        $methods[] = 'WC_Ingpsp_AfterPay';

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
        $settings = get_option('woocommerce_ingpsp_settings');
        $ing_klarna_ip_list = $settings['debug_klarna_ip'];

        if (strlen($ing_klarna_ip_list) > 0) {
            $ip_whitelist = array_map('trim', explode(",", $ing_klarna_ip_list));

            if (!in_array(WC_Geolocation::get_ip_address(), $ip_whitelist)) {
                unset($gateways['ingpsp_klarna']);
            }
        }

        return $gateways;
    }

    add_filter('woocommerce_available_payment_gateways', 'klarna_enabled_ip');
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_ingpsp');
    add_action('woocommerce_api_callback', array(new woocommerce_ingpsp(), 'handle_callback'));

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

    load_plugin_textdomain(WC_Ingpsp_Helper::DOMAIN, false, basename(dirname(__FILE__)).'/languages');

    /**
     * Support for Klarna and Afterpay order shipped state
     *
     * @param int $order_id
     */
    function ship_an_order($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order && $order->get_status() == 'shipped' && in_array($order->get_payment_method(), array('ingpsp_klarna', 'ingpsp_afterpay'))) {

            $settings = get_option('woocommerce_ingpsp_settings');
            $ap_settings = get_option('woocommerce_ingpsp_afterpay_settings');

            switch ($order->get_payment_method()) {
                case 'ingpsp_klarna':
                    $apiKey = ($settings['test_api_key'])?$settings['test_api_key']:$settings['api_key'];
                    break;
                case 'ingpsp_afterpay':
                    $apiKey = ($ap_settings['ap_test_api_key'])?$ap_settings['ap_test_api_key']:$settings['api_key'];
                    break;
            }
            
            if (strlen($apiKey) > 0) {
                try {
                    $ginger = \GingerPayments\Payment\Ginger::createClient($apiKey, $settings['psp_product']);
                    if ($settings['bundle_cacert'] == 'yes') {
                        $ginger->useBundledCA();
                    }
                } catch (Exception $exception) {
                    WC_Admin_Notices::add_custom_notice('ingpsp-error', $exception->getMessage());
                }
            }

            try {
                $id = get_post_meta($order_id, 'ing_order_id', true);
                $ginger->setOrderCapturedStatus($ginger->getOrder($id));
            } catch (\Exception $exception) {
                WC_Admin_Notices::add_custom_notice('ingpsp-error', $exception->getMessage());
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
    function ingpsp_order_received_text($text, $order)
    {
        return WC_Ingpsp_Helper::getOrderDescription($order->get_id());
    }

    /**
     * Filter out ING PSP AfterPay method if not in allowed countries.
     *
     * @param array $gateways
     * @return mixed
     */
    function afterpay_filter_gateways($gateways)
    {
        $settings = get_option('woocommerce_ingpsp_afterpay_settings');
        $ap_ip = $settings['ap_debug_ip'];

        if (strlen($ap_ip) > 0) {
            $ip_whitelist = array_map('trim', explode(",", $ap_ip));
            if (!in_array(WC_Geolocation::get_ip_address(), $ip_whitelist)) {
                unset($gateways['ingpsp_afterpay']);
            }
        } else if (isset(WC()->customer->billing['country']) && !in_array(WC()->customer->billing['country'], WC_Ingpsp_Helper::$afterPayCountries)) {
            unset($gateways['ingpsp_afterpay']);
        }

        return $gateways;
    }

    add_filter('woocommerce_available_payment_gateways', 'afterpay_filter_gateways', 10);
    add_filter('woocommerce_thankyou_order_received_text', 'ingpsp_order_received_text', 10, 2);
}
