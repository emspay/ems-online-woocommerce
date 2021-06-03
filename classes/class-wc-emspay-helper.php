<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_Emspay_Helper
 */
class WC_Emspay_Helper
{
    /**
     * Domain used for translations
     */
    const DOMAIN = 'emspay';

    /**
     * GINGER_ENDPOINT used for create Ginger client
     */
    const GINGER_ENDPOINT = 'https://api.online.emspay.eu';

    /**
     * EMS Online supported payment methods
     */
    public static $PAYMENT_METHODS = [
        'emspay_ideal',
        'emspay_bank-transfer',
        'emspay_credit-card',
        'emspay_bancontact',
        'emspay_klarna-pay-now',
        'emspay_paypal',
        'emspay_klarna-pay-later',
        'emspay_payconiq',
        'emspay_afterpay',
        'emspay_apple-pay',
        'emspay_pay-now',
        'emspay_amex',
        'emspay_tikkie-payment-request',
        'emspay_wechat',
    ];

    /**
     * @var array
     */
    public static $gingerAfterPayCountries = ['NL', 'BE'];

    /**
     * Method returns returns WC_Api callback URL
     *
     * @return string
     */
    public static function gingerGetReturnUrl()
    {
        return add_query_arg('wc-api', 'woocommerce_emspay', home_url('/'));
    }

    /**
     * Method formats the floating point amount to amount in cents
     *
     * @param float $total
     * @return int
     */
    public static function gingerGetAmountInCents($total)
    {
        return (int) round($total * 100);
    }

    /**
     * Method returns order total in cents based on current WooCommerce version.
     *
     * @param WC_Order $order
     * @return int
     */
    public static function gingerGerOrderTotalInCents(WC_Order $order)
    {
        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            $orderTotal = $order->get_total();
        } else {
            $orderTotal = $order->order->order_total;
        }

        return static::gingerGetAmountInCents($orderTotal);
    }

    /**
     * Method returns currencyCurrency in ISO-4217 format
     *
     * @return string
     */
    public static function gingerGetCurrency()
    {
        return get_woocommerce_currency();
    }

    /**
     * Method returns customer information from the order
     *
     * @param WC_Order $order
     * @return array
     */
    public static function gingerGetCustomerInfo(WC_Order $order)
    {
        $shipping_address = $order->get_address('shipping');
        $billing_address = $order->get_address('billing');

        if ($shipping_address['address_2'] == "" && $shipping_address['address_1'] == "") {$shipping_address = $billing_address;}

        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            $user_agent = $order->get_customer_user_agent();
            $ip_address = $order->get_customer_ip_address();
        } else {
            $user_agent = $order->customer_user_agent;
            $ip_address = $order->customer_ip_address;
        }

        $emspay_afterpay_date_of_birth_day = static::gingerGetCustomPaymentField('emspay_afterpay_date_of_birth_day');
        $emspay_afterpay_date_of_birth_month = static::gingerGetCustomPaymentField('emspay_afterpay_date_of_birth_month');
        $emspay_afterpay_date_of_birth_year = static::gingerGetCustomPaymentField('emspay_afterpay_date_of_birth_year');

        $birthdate = implode('-', [$emspay_afterpay_date_of_birth_year, $emspay_afterpay_date_of_birth_month, $emspay_afterpay_date_of_birth_day]);

        // removing it will make sure it gets removed if empty and thus not validated
        if ($birthdate == '--') {
            $birthdate = '';
        }

        return array_filter([
            'address_type' => 'customer',
            'merchant_customer_id' => (string) $order->get_user_id(),
            'email_address' => (string) $billing_address['email'],
            'first_name' => (string) $shipping_address['first_name'],
            'last_name' => (string) $shipping_address['last_name'],
            'address' => (string) trim($shipping_address['address_1'])
                .' '.trim($shipping_address['address_2'])
                .' '.trim(str_replace(' ', '', $shipping_address['postcode']))
                .' '.trim($shipping_address['city']),
            'postal_code' => (string) str_replace(' ', '', $shipping_address['postcode']),
            'country' => (string) $shipping_address['country'],
            'phone_numbers' => (array) [$billing_address['phone']],
            'user_agent' => (string) $user_agent,
            'ip_address' => (string) $ip_address,
            'locale' => (string) get_locale(),
            'gender' => (string) static::gingerGetCustomPaymentField('gender'),
            'birthdate' => (string) $birthdate,
            'additional_addresses' => [
                [
                    'address_type' => 'billing',
                    'address' => (string) trim($billing_address['address_1'])
                .' '.trim($billing_address['address_2'])
                .' '.trim(str_replace(' ', '', $billing_address['postcode']))
                .' '.trim($billing_address['city']),
                    'country' => (string) $billing_address['country'],
                ]
            ]
        ]);
    }
    /**
     * Method retrieves custom field from POST array.
     *
     * @param string $field
     * @return string|null
     */
    public static function gingerGetCustomPaymentField($field)
    {
        if (array_key_exists($field, $_POST) && strlen($_POST[$field]) > 0) {
            return sanitize_text_field($_POST[$field]);
        }

        return null;
    }

    /**
     * Get product price based on WooCommerce version.
     *
     * @param WC_Product $product
     * @return float|string
     */
    public static function gingerGetProductPrice($orderLine, $order)
    {
        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            return $order->get_item_total( $orderLine, true );
        } else {
            $product = $orderLine->get_product();
            return $product->get_price_including_tax();
        }
    }

    /**
     * Form helper for admin settings display
     *
     * @param string $type Form type
     * @return array
     */
    public static function gingerGetFormFields($type)
    {
        switch ($type) {
            case 'emspay_ideal':
                $default = __('iDEAL', self::DOMAIN);
                $label = __('Enable iDEAL Payments', self::DOMAIN);
                break;
            case 'emspay_credit-card':
                $default = __('Credit Card', self::DOMAIN);
                $label = __('Enable Credit Card Payments', self::DOMAIN);
                break;
            case 'emspay_bank-transfer':
                $default = __('Bank Transfer', self::DOMAIN);
                $label = __('Enable Bank Transfer Payments', self::DOMAIN);
                break;
            case 'emspay_klarna-pay-now':
                $default = __('Klarna Pay Now', self::DOMAIN);
                $label = __('Enable Klarna Pay Now Payments', self::DOMAIN);
                break;
            case 'emspay_bancontact':
                $default = __('Bancontact', self::DOMAIN);
                $label = __('Enable Bancontact Payments', self::DOMAIN);
                break;
            case 'emspay_paypal':
                $default = __('PayPal', self::DOMAIN);
                $label = __('Enable PayPal Payments', self::DOMAIN);
                break;
            case 'emspay_afterpay':
                $default = __('AfterPay', self::DOMAIN);
                $label = __('Enable AfterPay Payments', self::DOMAIN);
                $countries = self::$gingerAfterPayCountries;
                break;
            case 'emspay_klarna-pay-later':
                $default = __('Klarna Pay Later', self::DOMAIN);
                $label = __('Enable Klarna Pay Later Payments', self::DOMAIN);
                break;
            case 'emspay_payconiq':
                $default = __('Payconiq', self::DOMAIN);
                $label = __('Enable Payconiq Payments', self::DOMAIN);
                break;
	        case 'emspay_apple-pay':
		        $default = __('Apple Pay', self::DOMAIN);
		        $label = __('Enable Apple Pay Payments', self::DOMAIN);
		        break;
            case 'emspay_pay-now':
                $default = __('Pay Now', self::DOMAIN);
                $label = __('Enable Pay Now Payments', self::DOMAIN);
                break;
            case 'emspay_amex':
                $default = __('American Express', self::DOMAIN);
                $label = __('Enable American Express Payments', self::DOMAIN);
                break;
            case 'emspay_tikkie-payment-request':
                $default = __('Tikkie Payment Request', self::DOMAIN);
                $label = __('Enable Tikkie Payment Request Payments', self::DOMAIN);
                break;
            case 'emspay_wechat':
                $default = __('WeChat', self::DOMAIN);
                $label = __('Enable WeChat Payments', self::DOMAIN);
                break;
            default:
                $default = '';
                $label = '';
                break;
        }

        $formFields = [
            'enabled' => [
                'title' => __('Enable/Disable', self::DOMAIN),
                'type' => 'checkbox',
                'label' => $label,
                'default' => 'no'
            ],
            'title' => [
                'title' => __('Title', self::DOMAIN),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', self::DOMAIN),
                'default' => $default,
                'desc_tip' => true
            ],
        ];

        if ($type == 'emspay_afterpay') {
            $apFields = [
                'ap_test_api_key' => [
                    'title' => __('Test API key', WC_Emspay_Helper::DOMAIN),
                    'type' => 'text',
                    'description' => __('Test API key for testing implementation of AfterPay.', WC_Emspay_Helper::DOMAIN),
                ],
                'ap_debug_ip' => [
                    'title' => __('AfterPay Debug IP', WC_Emspay_Helper::DOMAIN),
                    'type' => 'text',
                    'description' => __('IP address for testing AfterPay. If empty, visible for all. If filled, only visible for specified IP addresses. (Example: 127.0.0.1, 255.255.255.255)', WC_Emspay_Helper::DOMAIN),
                ],
                'ap_countries_available' => [
                    'title' => __('Countries available for AfterPay', WC_Emspay_Helper::DOMAIN),
                    'type' => 'text',
                    'default' => $countries,
                    'description' => __('To allow AfterPay to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for AfterPay" field. Example: BE, NL, FR <br>  If field is empty then AfterPay will be available for all countries.', WC_Emspay_Helper::DOMAIN),
                ],
            ];

            $formFields = array_merge($formFields, $apFields);
        }

        return $formFields;
    }

    /**
     * Method returns payment method icon
     *
     * @param $method
     * @return null|string
     */
    public static function gingerGetIconSource($method)
    {
        if (in_array($method, self::$PAYMENT_METHODS)) {
            return '<img src="'.WC_HTTPS::force_https_url(EMSPAY_PLUGIN_URL."images/{$method}.png").'" />';
        }
    }

    /**
     * @param WC_Payment_Gateway $gateway
     * @return null|string
     */
    public static function gingerGetWebhookUrl()
    {
        return self::gingerGetReturnUrl();
    }

    /**
     * @param $order
     * @return array
     */
    public static function gingerGetOrderLines($order)
    {
        $orderLines = [];

        foreach ($order->get_items() as $orderLine) {
            $productId = (int) $orderLine->get_variation_id() ?: $orderLine->get_product_id();
            $image_url = wp_get_attachment_url($orderLine->get_product()->get_image_id());
            $orderLines[] = array_filter([
                'url' => get_permalink($productId),
                'name' => $orderLine->get_name(),
                'type' => 'physical',
                'amount' => static::gingerGetAmountInCents(static::gingerGetProductPrice($orderLine, $order)),
                'currency' => WC_Emspay_Helper::gingerGetCurrency(),
                'quantity' => (int) $orderLine->get_quantity(),
                'image_url' => ! empty($image_url) ? $image_url : null,
                'vat_percentage' => static::gingerGetAmountInCents(static::gingerGetProductTaxRate($orderLine->get_product())),
                'merchant_order_line_id' => (string) $productId
            ],
            function($value) {
	            return ! is_null($value);
	        });
        }

        if ($order->get_total_shipping() > 0) {
            $orderLines[] = static::gingerGetShippingOrderLine($order);
        }

        return $orderLines;
    }

    /**
     * Since single item in the cart can have multiple taxes,
     * we need to sum those taxes up.
     *
     * @param $product
     * @return int
     */
    public static function gingerGetProductTaxRate(WC_Product $product)
    {
        $WC_Tax = new WC_Tax();
        $totalTaxRate = 0;
        foreach ($WC_Tax->get_rates($product->get_tax_class()) as $taxRate) {
            $totalTaxRate += $taxRate['rate'];
        }
        return $totalTaxRate;
    }

    /**
     * @param $order
     * @return array
     */
    public static function gingerGetShippingOrderLine($order)
    {
        return [
            'name' => $order->get_shipping_method(),
            'type' => 'shipping_fee',
            'amount' => static::gingerGetAmountInCents($order->get_shipping_total() + $order->get_shipping_tax()),
            'currency' => WC_Emspay_Helper::gingerGetCurrency(),
            'vat_percentage' => static::gingerGetAmountInCents(static::gingerGetShippingTaxRate()),
            'quantity' => 1,
            'merchant_order_line_id' => (string) (count($order->get_items()) + 1)
        ];
    }

    /**
     * Since shipping fees can have multiple taxes applied,
     * we need to sum those taxes up.
     *
     * @return int
     */
    public static function gingerGetShippingTaxRate()
    {
        $totalTaxRate = 0;
        foreach (WC_Tax::get_shipping_tax_rates() as $taxRate) {
            $totalTaxRate += $taxRate['rate'];
        }
        return $totalTaxRate;
    }

    /**
     * Generate order description
     *
     * @param type $orderId
     * @return string
     */
    public static function gingerGetOrderDescription($orderId)
    {
        return sprintf(__('Your order %s at %s', self::DOMAIN), $orderId, get_bloginfo('name'));
    }

    /**
     * Get CA certificate path
     *
     * @return bool|string
     */
    public static function gingerGetCaCertPath(){
        return realpath(plugin_dir_path(__FILE__).'../assets/cacert.pem');
    }

    /**
     * Function gingerGetBillingCountry
     */
    public static function gingerGetBillingCountry() {
        return (! empty(WC()->customer) ? WC()->customer->get_billing_country() : false);
    }
}
