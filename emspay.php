<?php

/**
 * Plugin Name: EMS Online
 * Plugin URI: https://emspay.nl/
 * Description: EMS Pay WooCommerce plugin
 * Version: 1.2.4
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

require_once(untrailingslashit(plugin_dir_path(__FILE__)).'/vendor/autoload.php');

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
    function ginger_klarna_enabled_ip($gateways)
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

    add_filter('woocommerce_available_payment_gateways', 'ginger_klarna_enabled_ip');
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_emspay');
    add_action('woocommerce_api_callback', array(new woocommerce_emspay(), 'ginger_handle_callback'));

    function ginger_register_shipped_order_status()
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

    add_action('init', 'ginger_register_shipped_order_status');

    /**
     * @param array $order_statuses
     * @return array
     */
    function ginger_add_shipped_to_order_statuses(array $order_statuses)
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

    add_filter('wc_order_statuses', 'ginger_add_shipped_to_order_statuses');
    add_action('woocommerce_order_status_shipped', 'ginger_ship_an_order', 10, 2);
    add_action('woocommerce_refund_created', 'ginger_refund_an_order', 10, 2);
    add_action('woocommerce_order_item_add_action_buttons', 'ginger_add_refund_description');

    load_plugin_textdomain(WC_Emspay_Helper::DOMAIN, false, basename(dirname(__FILE__)).'/languages');

	/**
	 * Function ginger_refund_an_order - refund EMS order
	 *
	 * @param $refund_id
	 * @param $args
	 */
    function ginger_refund_an_order($refund_id, $args) {

		$ems_order_id = get_post_meta($args['order_id'], 'ems_order_id', true);
		$order = wc_get_order($args['order_id']);
		$ginger = ginger_get_client($order);
		$emsOrder = $ginger->getOrder($ems_order_id);
		$orderGateway = $order->get_payment_method();

		if($emsOrder['status'] !== 'completed') {
			throw new Exception( __( 'Only completed orders can be refunded', WC_Emspay_Helper::DOMAIN ));
		}
		
		$refund_data = [
			'amount' => WC_Emspay_Helper::gingerGetAmountInCents($args['amount']),
			'description' => 'OrderID: #' . $args['order_id'] . ', Reason: ' . $args['reason']
		];

		if( $orderGateway == 'emspay_klarna-pay-later' or $orderGateway == 'emspay_afterpay' ) {
			if(!isset($emsOrder['transactions']['flags']['has-captures'])) {
				throw new Exception( __('Refunds only possible when captured', WC_Emspay_Helper::DOMAIN ));
			};
			$refund_data['order_lines'] = WC_Emspay_Helper::gingerGetOrderLines($order);
		}

		update_post_meta($args['order_id'], 'refund_id', $refund_id);

		$ems_refund_order = $ginger->refundOrder(
			$ems_order_id,
			$refund_data
		);

		if( $ems_refund_order['status'] !== 'completed' ) {
                if( isset(current($ems_refund_order['transactions'])['customer_message']) ) {
                    throw new Exception( sprintf(__( 'Refund order is not completed: %s', WC_Emspay_Helper::DOMAIN ), current($ems_refund_order['transactions'])['customer_message']));
                } else {
                    throw new Exception( __( 'Refund order is not completed', WC_Emspay_Helper::DOMAIN ));
                }
            }
	}
	
	/**
	 * Function ginger_add_refund_description
	 *
	 * @param $order
	 */
	function ginger_add_refund_description($order) {
		echo "<p style='color: red; ' class='description'>" . esc_html__( "Please beware that for EMS transactions the refunds will process directly to the gateway!", WC_Emspay_Helper::DOMAIN) . "</p>";
	}

	/**
	 * Function ginger_ship_an_order - Support for Klarna and Afterpay order shipped state
	 *
	 * @param $order_id
	 * @param $order
	 */
    function ginger_ship_an_order($order_id, $order)
    {
        if ($order && $order->get_status() == 'shipped' && in_array($order->get_payment_method(), array('emspay_klarna-pay-later', 'emspay_afterpay'))) {

			$ginger = ginger_get_client($order);

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
	 * Function ginger_get_client
	 *
	 * @param $order
	 * @return \Ginger\ApiClient
	 */
    function ginger_get_client($order = []) {
		$settings = get_option('woocommerce_emspay_settings');
		$apiKey = $settings['api_key'];

		if(! empty($order)) {
                switch ($order->get_payment_method()) {
                    case 'emspay_klarna-pay-later':
                        $apiKey = ($settings['test_api_key'])?$settings['test_api_key']:$apiKey;
                        break;
                    case 'emspay_afterpay':
                        $ap_settings = get_option('woocommerce_emspay_afterpay_settings');
                        $apiKey = ($ap_settings['ap_test_api_key'])?$ap_settings['ap_test_api_key']:$apiKey;
                        break;
                }
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
						CURLOPT_CAINFO => WC_Emspay_Helper::gingerGetCaCertPath()
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
    function ginger_order_received_text($text, $order)
    {
        return WC_Emspay_Helper::gingerGetOrderDescription($order->get_id());
    }

    /**
     * Filter out EMS Online AfterPay method if not in allowed countries and IP.
     *
     * @param array $gateways
     * @return mixed
     */
    function ginger_afterpay_filter_gateway($gateways)
    {
        if ( ! is_checkout() ) {
            return $gateways;
        }

        unset($gateways['emspay']);
        $settings = get_option('woocommerce_emspay_afterpay_settings');

        // Filter AfterPay by IP option
        if ($settings['ap_debug_ip']) {
            $ip_whitelist = array_map('trim', explode(",", $settings['ap_debug_ip']));
            if (!in_array(WC_Geolocation::get_ip_address(), $ip_whitelist)) {
                unset($gateways['emspay_afterpay']);
                return $gateways;
            }
        }

        // Filter AfterPay by country available option
        if ($settings['ap_countries_available']) {
            $countrylist = array_map("trim", explode(',', $settings['ap_countries_available']));
            if (! WC_Emspay_Helper::gingerGetBillingCountry() or !in_array(WC_Emspay_Helper::gingerGetBillingCountry(), $countrylist)) {
                unset($gateways['emspay_afterpay']);
            }
        }

        return $gateways;
    }

    /**
     * Filter out EMS Online Klarna method if not in allowed IP.
     *
     * @param array $gateways
     * @return mixed
     */
    function ginger_klarna_filter_gateway($gateways)
    {
        if ( ! is_checkout() ) {
            return $gateways;
        }

        $settings = get_option('woocommerce_emspay_settings');

        // Filter Klarna by IP option
        if ($settings['debug_klarna_ip']) {
            $ip_whitelist = array_map('trim', explode(",", $settings['debug_klarna_ip']));

            if (!in_array(WC_Geolocation::get_ip_address(), $ip_whitelist)) {
                unset($gateways['emspay_klarna-pay-later']);
            }
        }

        return $gateways;
    }

    /**
     * Filter out EMS Online gateways by currencies.
     *
     * @param $gateways
     * @return bool
     */
    function ginger_filter_gateway_by_currency($gateways) {

        if ( ! is_checkout() ) {
            return $gateways;
        } else {
            wc_clear_notices();
        }

        $current_currency = get_woocommerce_currency();
        $client = ginger_get_client();

        if (empty($client)) {
            if(! wc_has_notice(__( 'API key is empty. Set API key and try again', WC_Emspay_Helper::DOMAIN ), 'error')) {
                wc_add_notice(__( 'API key is empty. Set API key and try again', WC_Emspay_Helper::DOMAIN ), 'error');
            }
            return false;
        }

        try {
            $allowed_currencies = $client->send('GET', '/merchants/self/projects/self/currencies');
        } catch (Exception $exception) {
            if(! wc_has_notice(sprintf(__('API Key is not valid: %s', WC_Emspay_Helper::DOMAIN), $exception->getMessage()), 'error')) {
                wc_add_notice(sprintf(__('API Key is not valid: %s', WC_Emspay_Helper::DOMAIN), $exception->getMessage()), 'error');
            }
            return false;
        }

        foreach ( $gateways as $key => $gateway ) {
            $currentMethod = strtr($gateway->id, ['emspay_' => '']);

            if(empty($allowed_currencies['payment_methods'][$currentMethod]['currencies'])) {
                continue;
            }

            if( ! in_array($current_currency, $allowed_currencies['payment_methods'][$currentMethod]['currencies']) ) {
                unset($gateways[$key]);
            }
        }

        return $gateways;
    }

    add_filter('woocommerce_available_payment_gateways', 'ginger_afterpay_filter_gateway', 10);
    add_filter('woocommerce_available_payment_gateways', 'ginger_klarna_filter_gateway', 10);
    add_filter('woocommerce_available_payment_gateways', 'ginger_filter_gateway_by_currency', 10);
    add_filter('woocommerce_thankyou_order_received_text', 'ginger_order_received_text', 10, 2);
}
