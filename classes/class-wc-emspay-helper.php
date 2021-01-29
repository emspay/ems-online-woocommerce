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
    public static $afterPayCountries = ['NL', 'BE'];

    /**
     * @var array
     */
    public static $supportedCurrencies = ['EUR'];

    /**
     * @var array
     */
    public static $supportedGatewayCurrencies = [
        'emspay_ideal' => ['EUR'],
        'emspay_bank-transfer' => ['EUR'],
        'emspay_credit-card' => ['EUR', 'GBP', 'USD', 'CAD', 'CHF', 'CZK', 'DKK', 'NOK', 'PLN', 'SEK', 'AUD', 'HKD', 'JPY', 'NZD', 'SGD', 'ZAR', 'BRL', 'TRY'],
        'emspay_bancontact' => ['EUR'],
        'emspay_klarna-pay-now' => ['EUR', 'DKK', 'NOK', 'SEK', 'CHF', 'GBP', 'USD', 'AUD'],
        'emspay_paypal' => ['AUD', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'NOK', 'PLN', 'SEK', 'SGD', 'USD'],
        'emspay_klarna-pay-later' => ['EUR', 'DKK', 'NOK', 'SEK', 'CHF', 'GBP', 'USD', 'AUD'],
        'emspay_payconiq' => ['EUR'],
        'emspay_afterpay' => ['EUR', 'NOK', 'SEK', 'DKK', 'CHF'],
        'emspay_apple-pay' => ['AUD', 'CHF', 'CAD', 'EUR', 'GBP', 'HKD', 'SGD', 'USD'],
        'emspay_amex' => ['AUD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'NOK', 'NZD', 'SEK', 'SGD', 'USD'],
        'emspay_tikkie-payment-request' => ['EUR'],
        'emspay_wechat' => ['EUR', 'USD', 'CNY', 'AUD', 'CAD', 'GBP', 'HKD', 'JPY', 'NZD', 'SGD', 'THB', 'KRW', 'RUB', 'DKK', 'SEK', 'CHF', 'NOK'],
    ];

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
        return get_woocommerce_currency();
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

        if ($shippems_address['address_2'] == "" && $shippems_address['address_1'] == "") {$shippems_address = $billems_address;}

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

        return array_filter([
            'address_type' => 'customer',
            'merchant_customer_id' => (string) $order->get_user_id(),
            'email_address' => (string) $billems_address['email'],
            'first_name' => (string) $shippems_address['first_name'],
            'last_name' => (string) $shippems_address['last_name'],
            'address' => (string) trim($shippems_address['address_1'])
                .' '.trim($shippems_address['address_2'])
                .' '.trim(str_replace(' ', '', $shippems_address['postcode']))
                .' '.trim($shippems_address['city']),
            'postal_code' => (string) str_replace(' ', '', $shippems_address['postcode']),
            'country' => (string) $shippems_address['country'],
            'phone_numbers' => (array) [$billems_address['phone']],
            'user_agent' => (string) $user_agent,
            'ip_address' => (string) $ip_address,
            'locale' => (string) get_locale(),
            'gender' => (string) static::getCustomPaymentField('gender'),
            'birthdate' => (string) $birthdate,
            'additional_addresses' => [
                [
                    'address_type' => 'billing',
                    'address' => (string) trim($billems_address['address_1'])
                .' '.trim($billems_address['address_2'])
                .' '.trim(str_replace(' ', '', $billems_address['postcode']))
                .' '.trim($billems_address['city']),
                    'country' => (string) $billems_address['country'],
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
            return $product->get_price_including_tax();
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
                $currencies = self::$supportedGatewayCurrencies['emspay_ideal'];
                break;
            case 'emspay_credit-card':
                $default = __('Credit Card', self::DOMAIN);
                $label = __('Enable Credit Card Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_credit-card'];
                break;
            case 'emspay_bank-transfer':
                $default = __('Bank Transfer', self::DOMAIN);
                $label = __('Enable Bank Transfer Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_bank-transfer'];
                break;
            case 'emspay_klarna-pay-now':
                $default = __('Klarna Pay Now', self::DOMAIN);
                $label = __('Enable Klarna Pay Now Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_klarna-pay-now'];
                break;
            case 'emspay_bancontact':
                $default = __('Bancontact', self::DOMAIN);
                $label = __('Enable Bancontact Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_bancontact'];
                break;
            case 'emspay_paypal':
                $default = __('PayPal', self::DOMAIN);
                $label = __('Enable PayPal Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_paypal'];
                break;
            case 'emspay_afterpay':
                $default = __('AfterPay', self::DOMAIN);
                $label = __('Enable AfterPay Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_afterpay'];
                $countries = self::$afterPayCountries;
                break;
            case 'emspay_klarna-pay-later':
                $default = __('Klarna Pay Later', self::DOMAIN);
                $label = __('Enable Klarna Pay Later Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_klarna-pay-later'];
                break;
            case 'emspay_payconiq':
                $default = __('Payconiq', self::DOMAIN);
                $label = __('Enable Payconiq Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_payconiq'];
                break;
	        case 'emspay_apple-pay':
		        $default = __('Apple Pay', self::DOMAIN);
		        $label = __('Enable Apple Pay Payments', self::DOMAIN);
                    $currencies = self::$supportedGatewayCurrencies['emspay_apple-pay'];
		        break;
            case 'emspay_pay-now':
                $default = __('Pay Now', self::DOMAIN);
                $label = __('Enable Pay Now Payments', self::DOMAIN);
                break;
            case 'emspay_amex':
                $default = __('American Express', self::DOMAIN);
                $label = __('Enable American Express Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_amex'];
                break;
            case 'emspay_tikkie-payment-request':
                $default = __('Tikkie Payment Request', self::DOMAIN);
                $label = __('Enable Tikkie Payment Request Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_tikkie-payment-request'];
                break;
            case 'emspay_wechat':
                $default = __('WeChat', self::DOMAIN);
                $label = __('Enable WeChat Payments', self::DOMAIN);
                $currencies = self::$supportedGatewayCurrencies['emspay_wechat'];
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
        if($type !== 'emspay_pay-now') {
            $formFields['allowed_currencies'] = [
                'title'    => __( 'Allowed currencies', self::DOMAIN ),
                'desc_tip' => __( 'Payment gateway will be available ONLY for selected currencies.', self::DOMAIN ) . ' ' .
                    __( 'If set empty - option is ignored.', self::DOMAIN ),
                'default'  => $currencies,
                'type'     => 'multiselect',
                'class'    => 'chosen_select',
                'css'      => 'width:100%',
                'options'  => get_woocommerce_currencies(),
                'custom_attributes' => array_merge( array( 'data-placeholder' => __( 'Select currencies...', self::DOMAIN ) )),
            ];
        };

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
    public static function getIconSource($method)
    {
        if (in_array($method, self::$PAYMENT_METHODS)) {
            return '<img src="'.WC_HTTPS::force_https_url(EMSPAY_PLUGIN_URL."images/{$method}.png").'" />';
        }
    }

    /**
     * @param WC_Payment_Gateway $gateway
     * @return null|string
     */
    public static function getWebhookUrl()
    {
        return self::getReturnUrl();
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
            $image_url = wp_get_attachment_url($orderLine->get_product()->get_image_id());
            $orderLines[] = array_filter([
                'url' => get_permalink($productId),
                'name' => $orderLine->get_name(),
                'type' => 'physical',
                'amount' => static::getAmountInCents(static::getProductPrice($orderLine, $order)),
                'currency' => 'EUR',
                'quantity' => (int) $orderLine->get_quantity(),
                'image_url' => ! empty($image_url) ? $image_url : null,
                'vat_percentage' => static::getAmountInCents(static::getProductTaxRate($orderLine->get_product())),
                'merchant_order_line_id' => (string) $productId
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
            'type' => 'shipping_fee',
            'amount' => static::getAmountInCents($order->get_shippems_total() + $order->get_shippems_tax()),
            'currency' => 'EUR',
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
    public static function getOrderDescription($orderId)
    {
        return sprintf(__('Your order %s at %s', self::DOMAIN), $orderId, get_bloginfo('name'));
    }

    /**
     * Get CA certificate path
     *
     * @return bool|string
     */
    public static function getCaCertPath(){
        return realpath(plugin_dir_path(__FILE__).'../assets/cacert.pem');
    }
}
