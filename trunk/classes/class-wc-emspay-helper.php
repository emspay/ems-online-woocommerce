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
     * EMS Online supported payment methods
     */
    public static $PAYMENT_METHODS = [
        'emspay_ideal',
        'emspay_banktransfer',
        'emspay_creditcard',
        'emspay_bancontact',
        'emspay_sofort',
        'emspay_paypal',
        'emspay_klarna',
        'emspay_payconiq',
        'emspay_afterpay',
    ];

    /**
     * @var array
     */
    public static $afterPayCountries = ['NL', 'BE'];

    /**
     * Method returns returns WC_Api callback URL
     *
     * @return string
     */
    public static function getReturnUrl()
    {
        return add_query_arg('wc-api', 'woocommerce_emspay', home_url('/'));
    }

    /**
     * Method formats the floating point amount to amount in cents
     *
     * @param float $total
     * @return int
     */
    public static function getAmountInCents($total)
    {
        return (int) round($total * 100);
    }

    /**
     * Method returns order total in cents based on current WooCommerce version.
     *
     * @param WC_Order $order
     * @return int
     */
    public static function gerOrderTotalInCents(WC_Order $order)
    {
        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            $orderTotal = $order->get_total();
        } else {
            $orderTotal = $order->order->order_total;
        }

        return static::getAmountInCents($orderTotal);
    }

    /**
     * Method returns currencyCurrency in ISO-4217 format
     *
     * @return string
     */
    public static function getCurrency()
    {
        return "EUR";
    }

    /**
     * Method returns customer information from the order
     *
     * @param WC_Order $order
     * @return array
     */
    public static function getCustomerInfo(WC_Order $order)
    {
        $shippems_address = $order->get_address('shipping');
        $billems_address = $order->get_address('billing');

        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            $user_agent = $order->get_customer_user_agent();
            $ip_address = $order->get_customer_ip_address();
        } else {
            $user_agent = $order->customer_user_agent;
            $ip_address = $order->customer_ip_address;
        }

        $emspay_afterpay_date_of_birth_day = static::getCustomPaymentField('emspay_afterpay_date_of_birth_day');
        $emspay_afterpay_date_of_birth_month = static::getCustomPaymentField('emspay_afterpay_date_of_birth_month');
        $emspay_afterpay_date_of_birth_year = static::getCustomPaymentField('emspay_afterpay_date_of_birth_year');

        $birthdate = implode('-', [$emspay_afterpay_date_of_birth_year, $emspay_afterpay_date_of_birth_month, $emspay_afterpay_date_of_birth_day]);

        // removing it will make sure it gets removed if empty and thus not validated
        if ($birthdate == '--') {
            $birthdate = '';
        }

        return \GingerPayments\Payment\Common\ArrayFunctions::withoutNullValues([
            'address_type' => 'customer',
            'merchant_customer_id' => $order->get_user_id(),
            'email_address' => $billems_address['email'],
            'first_name' => $shippems_address['first_name'],
            'last_name' => $shippems_address['last_name'],
            'address' => trim($shippems_address['address_1'])
                .' '.trim($shippems_address['address_2'])
                .' '.trim($shippems_address['postcode'])
                .' '.trim($shippems_address['city']),
            'postal_code' => $shippems_address['postcode'],
            'country' => $shippems_address['country'],
            'phone_numbers' => [$billems_address['phone']],
            'user_agent' => $user_agent,
            'ip_address' => $ip_address,
            'locale' => get_locale(),
            'gender' => static::getCustomPaymentField('gender'),
            'birthdate' => $birthdate,
            'additional_addresses' => [
                [
                    'address_type' => 'billing',
                    'address' => trim($billems_address['address_1'])
                .' '.trim($billems_address['address_2'])
                .' '.trim($billems_address['postcode'])
                .' '.trim($billems_address['city']),
                    'country' => $billems_address['country'],
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
    public static function getCustomPaymentField($field)
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
    public static function getProductPrice($orderLine, $order)
    {
        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            return $order->get_item_total( $orderLine, true );
        } else {
            $product = $orderLine->get_product();
            return $product->get_price_includems_tax();
        }
    }

    /**
     * Form helper for admin settings display
     *
     * @param string $type Form type
     * @return array
     */
    public static function getFormFields($type)
    {
        switch ($type) {
            case 'emspay_ideal':
                $default = __('iDEAL', self::DOMAIN);
                $label = __('Enable iDEAL Payments', self::DOMAIN);
                break;
            case 'emspay_creditcard':
                $default = __('Credit Card', self::DOMAIN);
                $label = __('Enable Credit Card Payments', self::DOMAIN);
                break;
            case 'emspay_banktransfer':
                $default = __('Bank Transfer', self::DOMAIN);
                $label = __('Enable Bank Transfer Payments', self::DOMAIN);
                break;
            case 'emspay_sofort':
                $default = __('SOFORT', self::DOMAIN);
                $label = __('Enable SOFORT Payments', self::DOMAIN);
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
                break;
            case 'emspay_klarna':
                $default = __('Klarna', self::DOMAIN);
                $label = __('Enable Klarna Payments', self::DOMAIN);
                break;
            case 'emspay_payconiq':
                $default = __('Payconiq', self::DOMAIN);
                $label = __('Enable Payconiq Payments', self::DOMAIN);
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
            'enable_webhook' => [
                'title' => __('Use Webhook URL', self::DOMAIN),
                'label' => __('Automatically include Webhook URL with every order', self::DOMAIN),
                'description' => __('API will request this URL in order to update transaction details.', self::DOMAIN),
                'type' => 'checkbox',
                'desc_tip' => true
            ]
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
    public static function getIconSource($method)
    {
        if (in_array($method, self::$PAYMENT_METHODS)) {
            return '<img src="'.WC_HTTPS::force_https_url(plugins_url()."/emspay/images/{$method}.png").'" />';
        }
    }

    /**
     * @param WC_Payment_Gateway $gateway
     * @return null|string
     */
    public static function getWebhookUrl(WC_Payment_Gateway $gateway)
    {
        return ($gateway->get_option('enable_webhook') == 'yes') ? self::getReturnUrl() : null;
    }

    /**
     * @param $order
     * @return array
     */
    public static function getOrderLines($order)
    {
        $orderLines = [];

        foreach ($order->get_items() as $orderLine) {
            $productId = (int) $orderLine->get_variation_id() ?: $orderLine->get_product_id();
            $orderLines[] = array_filter([
                'url' => get_permalink($productId),
                'name' => $orderLine->get_name(),
                'type' => \GingerPayments\Payment\Order\OrderLine\Type::PHYSICAL,
                'amount' => static::getAmountInCents(static::getProductPrice($orderLine, $order)),
                'currency' => \GingerPayments\Payment\Currency::EUR,
                'quantity' => (int) $orderLine->get_quantity(),
                'image_url' => wp_get_attachment_url($orderLine->get_product()->get_image_id()),
                'vat_percentage' => static::getAmountInCents(static::getProductTaxRate($orderLine->get_product())),
                'merchant_order_line_id' => $productId
            ],
            function($value) {
	            return ! is_null($value);
	        });
        }

        if ($order->get_total_shipping() > 0) {
            $orderLines[] = static::getShippingOrderLine($order);
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
    public static function getProductTaxRate(WC_Product $product)
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
    public static function getShippingOrderLine($order)
    {
        return [
            'name' => $order->get_shippems_method(),
            'type' => \GingerPayments\Payment\Order\OrderLine\Type::SHIPPING_FEE,
            'amount' => static::getAmountInCents($order->get_shippems_total() + $order->get_shippems_tax()),
            'currency' => \GingerPayments\Payment\Currency::EUR,
            'vat_percentage' => static::getAmountInCents(static::getShippingTaxRate()),
            'quantity' => 1,
            'merchant_order_line_id' => count($order->get_items()) + 1
        ];
    }

    /**
     * Since shipping fees can have multiple taxes applied,
     * we need to sum those taxes up.
     *
     * @return int
     */
    public static function getShippingTaxRate()
    {
        $totalTaxRate = 0;
        foreach (WC_Tax::get_shippems_tax_rates() as $taxRate) {
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
    public static function getOrderDescription($orderId)
    {
        return sprintf(__('Your order %s at %s', self::DOMAIN), $orderId, get_bloginfo('name'));
    }

    /**
     * @param $order
     */
    public static function reduceStock($order)
    {
        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            if ( ! get_post_meta( $order->id, '_order_stock_reduced', $single = true ) )
                wc_reduce_stock_levels($order->get_id());
        } else {
            if ( ! get_post_meta( $order->id, '_order_stock_reduced', $single = true ) )
                $order->reduce_order_stock();
        }
    }
}
