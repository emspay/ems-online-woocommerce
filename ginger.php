<?php
/**
 * Plugin Name: EMS Online
 * Plugin URI: https://emspay.nl/
 * Description: EMS Pay WooCommerce plugin
 * Version: 1.3.3
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
 * Define the Plugin version
 */
define('GINGER_PLUGIN_VERSION', 'WooCommerce v' . get_file_data(__FILE__, array('Version'), 'plugin')[0]);
define('GINGER_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', 'woocommerce_ginger_init', 0);

spl_autoload_register(function ($class)
{
    $file = str_replace('_', '-', strtolower($class));
    $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/classes/class-'.$file.'.php';
    if(!is_file($filepath)) $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/interfaces/'.$class.'.php'; //trying to find file in interfaces dir
    if (is_readable($filepath) && is_file($filepath)) require_once($filepath);
});

require_once(untrailingslashit(plugin_dir_path(__FILE__)).'/vendor/autoload.php');


function woocommerce_ginger_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Just an alias for API callback action
    class woocommerce_ginger extends WC_Ginger_Callback
    {
    }

    function woocommerce_add_ginger($methods)
    {
        return array_merge($methods, WC_Ginger_BankConfig::$WC_BANK_PAYMENT_METHODS);
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_ginger');
    add_action('woocommerce_api_callback', array(new woocommerce_ginger(), 'ginger_handle_callback'));

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
        foreach ($order_statuses as $key => $status)
        {
            $new_order_statuses[$key] = $status;
            if ('wc-processing' === $key) $new_order_statuses['wc-shipped'] = 'Shipped';
        }
        return $new_order_statuses;
    }

    add_filter('wc_order_statuses', 'ginger_add_shipped_to_order_statuses');
    add_action('woocommerce_order_status_shipped', 'ginger_ship_an_order', 10, 2);
    add_action('woocommerce_refund_created', 'ginger_refund_an_order', 10, 2);
    add_action('woocommerce_order_item_add_action_buttons', 'ginger_add_refund_description');

    load_plugin_textdomain(WC_Ginger_BankConfig::BANK_PREFIX, false, basename(dirname(__FILE__)).'/languages');

    /**
     * Function ginger_refund_an_order - refund Ginger order
     *
     * @param $refund_id
     * @param $args
     */
    function ginger_refund_an_order($refund_id, $args)
    {
        $gingerOrderID = get_post_meta($args['order_id'], WC_Ginger_BankConfig::BANK_PREFIX.'_order_id', true);
        $order = wc_get_order($args['order_id']);
        if (!strstr($order->data['payment_method'],WC_Ginger_BankConfig::BANK_PREFIX)) return true; //order was not paid by bank's payment method
        $client = WC_Ginger_Clientbuilder::gingerBuildClient($order->get_payment_method());
        $gingerOrder = $client->getOrder($gingerOrderID);

        if($gingerOrder['status'] !== 'completed')
        {
            throw new Exception( __( 'Only completed orders can be refunded', WC_Ginger_BankConfig::BANK_PREFIX ));
        }

        $orderBuilder = new WC_Ginger_Orderbuilder();
        $refundData = [
            'amount' => $orderBuilder->gingerGetAmountInCents($args['amount']),
            'description' => 'OrderID: #' . $args['order_id'] . ', Reason: ' . $args['reason']
        ];

        if(in_array($order->get_payment_method(),WC_Ginger_Helper::GATEWAYS_SUPPORT_CAPTURING))
        {
            if(!in_array('has-captures',$gingerOrder['flags']))
            {
                throw new Exception(__('Refunds only possible when captured', WC_Ginger_BankConfig::BANK_PREFIX));
            };
            $refundData['order_lines'] = $orderBuilder->gingerGetOrderLines($order);
        }

        update_post_meta($args['order_id'], 'refund_id', $refund_id);

        $gingerRefundOrder = $client->refundOrder(
            $gingerOrderID,
            $refundData
        );

        if($gingerRefundOrder['status'] !== 'completed')
        {
            if(current($gingerRefundOrder['transactions'])['customer_message']){
                throw new Exception( sprintf(
                    __('Refund order is not completed: %s', WC_Ginger_BankConfig::BANK_PREFIX),
                    current($gingerRefundOrder['transactions'])['customer_message']
                ));
            }else{
                throw new Exception(__( 'Refund order is not completed', WC_Ginger_BankConfig::BANK_PREFIX));
            }
        }
    }

    /**
     * Function ginger_add_refund_description
     *
     * @param $order
     */
    function ginger_add_refund_description($order)
    {
        if (strstr($order->data['payment_method'],WC_Ginger_BankConfig::BANK_PREFIX)) //shows only for orders which were paid by bank's payment method
        {
            echo "<p style='color: red; ' class='description'>" . esc_html__( "Please beware that for bank transactions the refunds will process directly to the gateway!", WC_Ginger_BankConfig::BANK_PREFIX) . "</p>";
        }
    }

    /**
     * Function ginger_ship_an_order - Support for Klarna and Afterpay order shipped state
     *
     * @param $order_id
     * @param $order
     */
    function ginger_ship_an_order($order_id, $order)
    {
        if ($order->get_status() == 'shipped' && in_array($order->get_payment_method(),WC_Ginger_Helper::GATEWAYS_SUPPORT_CAPTURING))
        {
            $client = WC_Ginger_Clientbuilder::gingerBuildClient($order->get_payment_method());
            try {
                $id = get_post_meta($order_id, WC_Ginger_BankConfig::BANK_PREFIX.'_order_id', true);
                $gingerOrder = $client->getOrder($id);
                $transactionID = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['id'] : null;
                $client->captureOrderTransaction($gingerOrder['id'], $transactionID);
            } catch (\Exception $exception) {
                WC_Admin_Notices::add_custom_notice('ginger-error', $exception->getMessage());
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
    function ginger_order_received_text($text, $order)
    {
        $orderBuilder = new WC_Ginger_Orderbuilder();
        $orderBuilder->gingerSetMerchantOrderID($order->get_id());

        return $orderBuilder->gingerGetOrderDescription();
    }


    /**
     * Filter out The plugin gateways by currencies.
     *
     * @param $gateways
     * @return bool
     */
    function ginger_filter_gateway_by_currency($gateways)
    {
        if (!is_checkout()) return $gateways;
        wc_clear_notices();

        if (!(int)preg_grep('/'.WC_Ginger_BankConfig::BANK_PREFIX.'/', array_keys($gateways))) return $gateways;//if gateways aren't contain bank's methods, further validations is unnecessary

        $current_currency = get_woocommerce_currency();
        $client = WC_Ginger_Clientbuilder::gingerBuildClient();

        if (!$client)
        {
            if(!wc_has_notice(__( 'API key is empty. '.WC_Ginger_BankConfig::BANK_LABEL.' payment methods deactivated', WC_Ginger_BankConfig::BANK_PREFIX ), 'notice'))
            {
                wc_add_notice(__( 'API key is empty. '.WC_Ginger_BankConfig::BANK_LABEL.' payment methods deactivated', WC_Ginger_BankConfig::BANK_PREFIX ), 'notice');
            }
            foreach ($gateways as $key => $gateway)
            {
                if ($gateway instanceof GingerAdditionalTestingEnvironment)
                {
                    if (WC_Ginger_Clientbuilder::gingerBuildClient($gateway->id)) continue;
                }
                if (strstr($gateway->id,WC_Ginger_BankConfig::BANK_PREFIX)) unset($gateways[$key]);
            }
            return $gateways;
        }

        try {
            $allowed_currencies = $client->getCurrencyList();
        } catch (Exception $exception) {
            //Unfortunately $exception->getCode() is empty hence we find error code in $exception->getMessage()
            if (strstr($exception->getMessage(),"Unauthorized(401)"))
            {
                if(!wc_has_notice(sprintf(__('API Key is not valid: %s '.WC_Ginger_BankConfig::BANK_LABEL.' payment methods deactivated', WC_Ginger_BankConfig::BANK_PREFIX), $exception->getMessage()), 'notice'))
                {
                    wc_add_notice(sprintf(__('API Key is not valid: %s '.WC_Ginger_BankConfig::BANK_LABEL.' payment methods deactivated', WC_Ginger_BankConfig::BANK_PREFIX), $exception->getMessage()), 'notice');
                }
                foreach ($gateways as $key => $gateway) if (strstr($gateway->id,WC_Ginger_BankConfig::BANK_PREFIX)) unset($gateways[$key]);
            }

            if (strstr($exception->getMessage(),"Forbidden(403)"))
            {
                if(!wc_has_notice(sprintf(__('API Key has not permission for some action: %s Please contact technical support!', WC_Ginger_BankConfig::BANK_PREFIX), $exception->getMessage()), 'notice'))
                {
                    wc_add_notice(sprintf(__('API Key has not permission for some action: %s Please contact technical support!', WC_Ginger_BankConfig::BANK_PREFIX), $exception->getMessage()), 'notice');
                }
            }

            return $gateways; //return gateways without currency validation when merchant's api-key hasn't access to api
        }

        $notAvailableGateways = "";
        foreach ($gateways as $key => $gateway)
        {
            if (!strstr($gateway->id,WC_Ginger_BankConfig::BANK_PREFIX)) continue; //skip woocommerce default payment methods
            $currentMethod = str_replace(WC_Ginger_BankConfig::BANK_PREFIX.'_','',$gateway->id);
            if(!array_key_exists($currentMethod, $allowed_currencies['payment_methods']) || !$allowed_currencies['payment_methods'][$currentMethod]['currencies']) continue;
            if(!in_array($current_currency, $allowed_currencies['payment_methods'][$currentMethod]['currencies']))
            {
                $notAvailableGateways .= $gateway->method_title."; <br>";
                unset($gateways[$key]);
            }
        }
        if($notAvailableGateways && !wc_has_notice(__('The following payment methods are not available for selected currency: <br>'.$notAvailableGateways, WC_Ginger_BankConfig::BANK_PREFIX), 'notice'))
        {
            wc_add_notice(__('The following payment methods are not available for selected currency: <br>'.$notAvailableGateways, WC_Ginger_BankConfig::BANK_PREFIX), 'notice');
        }
        return $gateways;
    }

    /**
     * Filter out The plugin gateways by countries and IPs.
     * @param $gateways
     * @return mixed
     */
    function ginger_additional_filter_gateways($gateways)
    {
        if (!is_checkout()) return $gateways;
        unset($gateways['ginger']);
        foreach ($gateways as $key => $gateway)
        {
            if (!strstr($gateway->id,WC_Ginger_BankConfig::BANK_PREFIX)) continue; //skip woocommerce default payment methods
            $settings = get_option('woocommerce_'.$gateway->id.'_settings');

            if($gateway instanceof GingerCountryValidation)
            {
                if (array_key_exists('countries_available', $settings) && $settings['countries_available'])
                {
                    $countryList = array_map("trim", explode(',', $settings['countries_available']));
                    if (!WC_Ginger_Helper::gingerGetBillingCountry() || !in_array(WC_Ginger_Helper::gingerGetBillingCountry(), $countryList))
                    {
                        unset($gateways[$key]);
                        continue;
                    }
                }
            }

            if ($gateway instanceof GingerAdditionalTestingEnvironment)
            {
                if (array_key_exists('debug_ip', $settings) && $settings['debug_ip'])
                {
                    $whiteListIP = array_map('trim', explode(",", $settings['debug_ip']));
                    if (!in_array(WC_Geolocation::get_ip_address(), $whiteListIP))
                    {
                        unset($gateways[$key]);
                        continue;
                    }
                }

            }
        }

        return $gateways;
    }

    /**
     *  Function ginger_remove_notices
     */
    function ginger_remove_notices()
    {
        wc_clear_notices();
    }

    add_filter('woocommerce_available_payment_gateways', 'ginger_additional_filter_gateways', 10);
    add_filter('woocommerce_available_payment_gateways', 'ginger_filter_gateway_by_currency', 10);
    add_filter('woocommerce_thankyou_order_received_text', 'ginger_order_received_text', 10, 2);
    add_action('woocommerce_thankyou', 'ginger_remove_notices', 20);

}
