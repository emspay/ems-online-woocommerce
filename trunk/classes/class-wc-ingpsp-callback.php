<?php

if (!defined('ABSPATH')) {
    exit;
}

use \GingerPayments\Payment\Ginger;

class WC_Ingpsp_Callback extends WC_Ingpsp_Gateway
{
    public function __construct()
    {
        $this->id = 'ingpsp';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('ING PSP', WC_Ingpsp_Helper::DOMAIN);
        $this->method_description = __('ING PSP', WC_Ingpsp_Helper::DOMAIN);

        parent::__construct();
    }

    public function checkWarningTestMode()
    {
        if ($this->ing !== null && $this->ing->isInTestMode()) {
            echo '<h3><span class="dashicons dashicons-warning"></span>'
                .__('The current project is in test mode', WC_Ingpsp_Helper::DOMAIN)
                .'</h3>';
            echo '<p>'.__('In test mode you can only test iDEAL. When done testing, 
                    please login to the portal and activate your project.', WC_Ingpsp_Helper::DOMAIN).'</p>';
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'api_key' => [
                'title' => __('API key', WC_Ingpsp_Helper::DOMAIN),
                'type' => 'text',
                'description' => __('API key provided by ING', WC_Ingpsp_Helper::DOMAIN),
            ],
            'test_api_key' => [
                'title' => __('Test API key', WC_Ingpsp_Helper::DOMAIN),
                'type' => 'text',
                'description' => __('Test API key for testing implementation of Klarna.', WC_Ingpsp_Helper::DOMAIN),
            ],
            'debug_klarna_ip' => [
                'title' => __('Klarna Debug IP', WC_Ingpsp_Helper::DOMAIN),
                'type' => 'text',
                'description' => __('IP address for testing Klarna. If empty, visible for all. If filled, only visible for specified IP addresses. (Example: 127.0.0.1, 255.255.255.255)', WC_Ingpsp_Helper::DOMAIN),
            ],
            'psp_product' => [
                'title' => __('PSP Product', WC_Ingpsp_Helper::DOMAIN),
                'type' => 'select',
                'description' => __('PSP Product', WC_Ingpsp_Helper::DOMAIN),
                'options' => [
                    'kassacompleet' => __('Kassa Compleet', WC_Ingpsp_Helper::DOMAIN),
                    'ingcheckout' => __('ING Checkout', WC_Ingpsp_Helper::DOMAIN),
                    'epay' => __('ING ePay', WC_Ingpsp_Helper::DOMAIN)
                ],
            ],
            'failed_redirect' => [
                'title' => __('Failed payment page', WC_Ingpsp_Helper::DOMAIN),
                'description' => __(
                    'Page where user is redirected after payment has failed.',
                    WC_Ingpsp_Helper::DOMAIN
                ),
                'type' => 'select',
                'options' => [
                    'checkout' => __('Checkout Page', WC_Ingpsp_Helper::DOMAIN),
                    'cart' => __('Shopping Cart', WC_Ingpsp_Helper::DOMAIN)
                ],
                'default' => 'checkout',
                'desc_tip' => true
            ],
            'bundle_cacert' => [
                'title' => __('cURL CA bundle', WC_Ingpsp_Helper::DOMAIN),
                'label' => __('Use cURL CA bundle', WC_Ingpsp_Helper::DOMAIN),
                'description' => __(
                    'Resolves issue when curl.cacert path is not set in PHP.ini',
                    WC_Ingpsp_Helper::DOMAIN
                ),
                'type' => 'checkbox',
                'desc_tip' => true
            ]
        ];
    }

    public function handle_callback()
    {
        if (!empty($_GET['order_id'])) {
            $type = "return";
            $ing_order_id = $_GET['order_id'];
        } else {
            $type = "webhook";
            $input = json_decode(file_get_contents("php://input"), true);
            if (!in_array($input['event'], array("status_changed"))) {
                die("Only work to do if the status changed");
            }
            $ing_order_id = $input['order_id'];
        }

        // we potentially have 3 different API keys we can fetch the order with
        $settings = get_option('woocommerce_ingpsp_settings');
        $ap_settings = get_option('woocommerce_ingpsp_afterpay_settings');

        $success_get = false;
        // first try with standard API key
        try {
            $ingOrder = $this->ing->getOrder($ing_order_id);
            $success_get = true;
        } catch (Exception $exception) {
        }

        // second try with api key from Klarna
        if (!$success_get) {
            if ($settings['test_api_key']) {

                $this->ing = Ginger::createClient($settings['test_api_key'], $settings['psp_product']);
                if ($settings['bundle_cacert'] == 'yes') {
                    $this->ing->useBundledCA();
                }

                try {
                    $ingOrder = $this->ing->getOrder($ing_order_id);
                    $success_get = true;
                } catch (Exception $exception) {
                }
            }
        }

        // third try with api key from Afterpay
        if (!$success_get) {
            if ($ap_settings['ap_test_api_key']) {

                $this->ing = Ginger::createClient($ap_settings['ap_test_api_key'], $settings['psp_product']);
                if ($settings['bundle_cacert'] == 'yes') {
                    $this->ing->useBundledCA();
                }

                try {
                    $ingOrder = $this->ing->getOrder($ing_order_id);
                    $success_get = true;
                } catch (Exception $exception) {
                }
            }
        }

        if (!$success_get) {
            die("COULD NOT GET ORDER");
        }

        $order = new WC_Order($ingOrder->getMerchantOrderId());

        if ($type == "webhook") {
            if ($ingOrder->status()->isCompleted()) {
                $woo_version = get_option('woocommerce_version', 'Unknown');
                if (version_compare($woo_version, '2.2.0', '>=')) {
                    $order->payment_complete($ing_order_id);
                } else {
                    $order->payment_complete();
                }
            }

            if ($ingOrder->Transactions()->current()->getStatus() == 'captured') {
                if ($order->get_status() == 'processing')
                    $order->update_status('shipped', 'Order updated to shipped, transactions was captured', false);
            }

            exit;
        }

        if ($ingOrder->status()->isCompleted() || $ingOrder->status()->isProcessing()) {
            header("Location: ".$this->get_return_url($order));
            exit;
        } else {
            wc_add_notice(__('There was a problem processing your transaction.', WC_Ingpsp_Helper::DOMAIN), 'error');
            if ($this->get_option('failed_redirect') == 'cart') {
                $url = $order->get_cancel_order_url();
            } else {
                $url = $order->get_checkout_payment_url();
            }
            header("Location: ".str_replace("&amp;", "&", $url));
            exit;
        }
    }
}
