<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class WC_Ginger_Helper
 */
class WC_Ginger_Helper
{

	/**
	 * List of payment methods that support capturing
	 */
	const GATEWAYS_SUPPORT_CAPTURING = [
		WC_Ginger_BankConfig::BANK_PREFIX . '_afterpay',
		WC_Ginger_BankConfig::BANK_PREFIX . '_klarna-pay-later',
		WC_Ginger_BankConfig::BANK_PREFIX . '_klarna-direct-debit',
	];

	/**
	 * Method retrieves custom field from POST array.
	 *
	 * @param string $field
	 * @return string|null
	 */
	public static function gingerGetCustomPaymentField($field)
	{
		if (array_key_exists($field, $_POST) && strlen($_POST[$field]) > 0) return sanitize_text_field($_POST[$field]);
		return null;
	}

	/**
	 * Form helper for admin settings display
	 *
	 * @param string $gateway - payment method
	 * @return array
	 */
	public static function gingerGetFormFields($gateway)
	{
		switch (str_replace(WC_Ginger_BankConfig::BANK_PREFIX . '_', '', $gateway->id)) {
			case 'ideal':
				$default = __('iDEAL', "emspay");
				$label = __('Enable iDEAL Payments', "emspay");
				break;
			case 'credit-card':
				$default = __('Credit Card', "emspay");
				$label = __('Enable Credit Card Payments', "emspay");
				break;
			case 'bank-transfer':
				$default = __('Bank Transfer', "emspay");
				$label = __('Enable Bank Transfer Payments', "emspay");
				break;
			case 'klarna-pay-now':
				$default = __('Klarna Pay Now', "emspay");
				$label = __('Enable Klarna Pay Now Payments', "emspay");
				break;
			case 'bancontact':
				$default = __('Bancontact', "emspay");
				$label = __('Enable Bancontact Payments', "emspay");
				break;
			case 'paypal':
				$default = __('PayPal', "emspay");
				$label = __('Enable PayPal Payments', "emspay");
				break;
			case 'afterpay':
				$default = __('AfterPay', "emspay");
				$label = __('Enable AfterPay Payments', "emspay");
				break;
			case 'klarna-pay-later':
				$default = __('Klarna Pay Later', "emspay");
				$label = __('Enable Klarna Pay Later Payments', "emspay");
				break;
			case 'payconiq':
				$default = __('Payconiq', "emspay");
				$label = __('Enable Payconiq Payments', "emspay");
				break;
			case 'apple-pay':
				$default = __('Apple Pay', "emspay");
				$label = __('Enable Apple Pay Payments', "emspay");
				break;
			case 'pay-now':
				$default = __('Pay Now', "emspay");
				$label = __('Enable Pay Now Payments', "emspay");
				break;
			case 'amex':
				$default = __('American Express', "emspay");
				$label = __('Enable American Express Payments', "emspay");
				break;
			case 'tikkie-payment-request':
				$default = __('Tikkie Payment Request', "emspay");
				$label = __('Enable Tikkie Payment Request Payments', "emspay");
				break;
			case 'wechat':
				$default = __('WeChat', "emspay");
				$label = __('Enable WeChat Payments', "emspay");
				break;
			case 'google-pay':
				$default = __('Google Pay', "emspay");
				$label = __('Enable Google Pay Payments', "emspay");
				break;
			case 'klarna-direct-debit':
				$default = __('Klarna Direct Debit', "emspay");
				$label = __('Enable Klarna Direct Debit Payments', "emspay");
				break;
			case 'sofort':
				$default = __('Sofort', "emspay");
				$label = __('Enable Sofort Payments', "emspay");
				break;
			case 'ginger':
				return [
					'lib_title' => [
						'title' => __('Title', "emspay"),
						'type' => 'text',
						'description' => __('This is the general module with settings, during checkout the user will not see this option.', "emspay"),
						'default' => __('Plugin', "emspay")
					],
					'api_key' => [
						'title' => __('API key', "emspay"),
						'type' => 'text',
						'description' => __('API key provided by ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay"),
					],
					'failed_redirect' => [
						'title' => __('Failed payment page', "emspay"),
						'description' => __(
							'Page where user is redirected after payment has failed.',
							"emspay"
						),
						'type' => 'select',
						'options' => [
							'checkout' => __('Checkout Page', "emspay"),
							'cart' => __('Shopping Cart', "emspay")
						],
						'default' => 'checkout',
						'desc_tip' => true
					],
					'bundle_cacert' => [
						'title' => __('cURL CA bundle', "emspay"),
						'label' => __('Use cURL CA bundle', "emspay"),
						'description' => __(
							'Resolves issue when curl.cacert path is not set in PHP.ini',
							"emspay"
						),
						'type' => 'checkbox',
						'desc_tip' => true
					],
					'auto_complete' => [
						'title' => __('Auto complete orders', "emspay"),
						'label' => __('Enable automatically change store orders status to Completed after receiving payment', "emspay"),
						'type' => 'checkbox'
					]
				];
			default:
				$default = '';
				$label = '';
				break;
		}

		$formFields = [
			'enabled' => [
				'title' => __('Enable/Disable', "emspay"),
				'type' => 'checkbox',
				'label' => $label,
				'default' => 'no'
			],
			'title' => [
				'title' => __('Title', "emspay"),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', "emspay"),
				'default' => $default,
				'desc_tip' => true
			],
		];

		if ($gateway instanceof GingerAdditionalTestingEnvironment) {
			$additionalFields = [
				'test_api_key' => [
					'title' => __('Test API key', "emspay"),
					'type' => 'text',
					'description' => __('Test API key for testing implementation ' . $gateway->method_title, "emspay"),
				],
				'debug_ip' => [
					'title' => __('AfterPay Debug IP', "emspay"),
					'type' => 'text',
					'description' => __('IP address for testing ' . $gateway->method_title . '. If empty, visible for all. If filled, only visible for specified IP addresses. (Example: 127.0.0.1, 255.255.255.255)', "emspay"),
				],
			];

			$formFields = array_merge($formFields, $additionalFields);
		}

		if ($gateway instanceof GingerCountryValidation) {
			$additionalFields = [
				'countries_available' => [
					'title' => __('Countries available for ' . $gateway->method_title, "emspay"),
					'type' => 'text',
					'default' => implode(', ', self::gingerGetAvailableCountries($gateway->id)),
					'description' => __('To allow ' . $gateway->method_title . ' to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for ' . $gateway->method_title . '" field. Example: BE, NL, FR <br>  If field is empty then ' . $gateway->method_title . ' will be available for all countries.', "emspay"),
				]
			];
			$formFields = array_merge($formFields, $additionalFields);
		}

		return $formFields;
	}

	public static function gingerGetAvailableCountries($gateway): array
	{
		$countryMapping = [
			WC_Ginger_BankConfig::BANK_PREFIX . '_afterpay' => ['NL', 'BE'],
		];
		return $countryMapping[$gateway];
	}
	/**
	 * Method returns payment method icon
	 *
	 * @param $method
	 * @return null|string
	 */
	public static function gingerGetIconSource($method)
	{
		if (in_array($method, WC_Ginger_BankConfig::$BANK_PAYMENT_METHODS)) {
			$imageTitle = str_replace(WC_Ginger_BankConfig::BANK_PREFIX, 'ginger', $method);
			$imageType = $imageTitle == 'ginger_pay-now' ? 'png' : 'svg';
			$imagePath = GINGER_PLUGIN_URL . "images/{$imageTitle}.$imageType";
			return '<img src="' . WC_HTTPS::force_https_url($imagePath) . '" />';
		}
	}

	/**
	 * Function gingerGetBillingCountry
	 */
	public static function gingerGetBillingCountry()
	{
		return (WC()->customer ? WC()->customer->get_billing_country() : false);
	}
}
