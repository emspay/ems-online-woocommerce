<?php

/**
 * Plugin Name: EMS Online
 * Plugin URI: https://emspay.nl/
 * Description: EMS Pay WooCommerce plugin
 * Version: 1.0.12
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
define('EMSPAY_PLUGIN_VERSION', 'WooCommerce v' . get_file_data(__FILE__, array('Version'), 'plugin')[0]);
define('EMSPAY_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', 'woocommerce_emspay_init', 0);

spl_autoload_register(function ($class) {
    $file = str_replace('_', '-', strtolower($class));
    $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/classes/class-'.$file.'.php';

    if (is_readable($filepath) && is_file($filepath)) {
        require_once($filepath);
    }
});

require_once(untrailingslashit(plugin_dir_path(__FILE__)).'/ginger-php/vendor/autoload.php');

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
        $methods = [
            'WC_Emspay_Callback',
            'WC_Emspay_Ideal',
            'WC_Emspay_Banktransfer',
            'WC_Emspay_Bancontact',
            'WC_Emspay_Creditcard',
            'WC_Emspay_PayPal',
            'WC_Emspay_KlarnaPayLater',
            'WC_Emspay_KlarnaPayNow',
            'WC_Emspay_Payconiq',
            'WC_Emspay_AfterPay',
            'WC_Emspay_ApplePay',
            'WC_Emspay_PayNow',
            'WC_Emspay_Amex',
            'WC_Emspay_TikkiePaymentRequest',
            'WC_Emspay_WeChat',
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
                unset($gateways['emspay_klarna-pay-later']);
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

    add_action('woocommerce_order_status_shipped', 'ship_an_order', 10, 2);
    add_action('woocommerce_refund_created', 'refund_an_order', 10, 2);

    load_plugin_textdomain(WC_Emspay_Helper::DOMAIN, false, basename(dirname(__FILE__)).'/languages');

	/**
	 * Function refund_an_order - refund EMS order
	 *
	 * @param $refund_id
	 * @param $args
	 */
    function refund_an_order($refund_id, $args) {

		try {
			$ems_order_id = get_post_meta($args['order_id'], 'ems_order_id', true);
			$order = wc_get_order($args['order_id']);
			$ginger = get_ginger_client($order);

			update_post_meta($args['order_id'], 'refund_id', $refund_id);

			$ginger->refundOrder(
				$ems_order_id,
				['amount' => (int) $args['amount'], 'description' => 'OrderID: #' . $args['order_id'] . ', Reason: ' . $args['reason']]
			);
		} catch (\Exception $exception) {
			WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
		}
	}

	/**
	 * Function ship_an_order - Support for Klarna and Afterpay order shipped state
	 *
	 * @param $order_id
	 * @param $order
	 */
    function ship_an_order($order_id, $order)
    {
        if ($order && $order->get_status() == 'shipped' && in_array($order->get_payment_method(), array('emspay_klarna-pay-later', 'emspay_afterpay'))) {

			$ginger = get_ginger_client($order);

            try {
                $id = get_post_meta($order_id, 'ems_order_id', true);
                $ems_order = $ginger->getOrder($id);
                $transaction_id = !empty(current($ems_order['transactions'])) ? current($ems_order['transactions'])['id'] : null;
                $ginger->captureOrderTransaction($ems_order['id'], $transaction_id);
            } catch (\Exception $exception) {
                WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
            }
        }
    }

	/**
	 * Function get_ginger_client
	 *
	 * @param $order
	 * @return \Ginger\ApiClient
	 */
    function get_ginger_client($order) {
		$settings = get_option('woocommerce_emspay_settings');
		$apiKey = $settings['api_key'];

		switch ($order->get_payment_method()) {
			case 'emspay_klarna-pay-later':
				$apiKey = ($settings['test_api_key'])?$settings['test_api_key']:$apiKey;
				break;
			case 'emspay_afterpay':
				$ap_settings = get_option('woocommerce_emspay_afterpay_settings');
				$apiKey = ($ap_settings['ap_test_api_key'])?$ap_settings['ap_test_api_key']:$apiKey;
				break;
		}

		if (! $apiKey) {
			return false;
		}

		try {
			$ginger = \Ginger\Ginger::createClient(
				WC_Emspay_Helper::GINGER_ENDPOINT,
				$apiKey,
				($settings['bundle_cacert'] == 'yes') ?
					[
						CURLOPT_CAINFO => WC_Emspay_Helper::getCaCertPath()
					] : []
			);
		} catch (Exception $exception) {
			WC_Admin_Notices::add_custom_notice('emspay-error', $exception->getMessage());
		}

		return $ginger;
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
