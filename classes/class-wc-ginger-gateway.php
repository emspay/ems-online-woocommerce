<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_Ginger_Gateway extends WC_Payment_Gateway
{
	protected $gingerClient;
	protected $ginger_settings;
	protected $merchant_order_id;
	protected $woocommerceOrder;

	public function __construct()
	{

		$this->ginger_init_form_fields();
		$this->init_settings();

		$this->title = $this->id == 'ginger' ?  $this->get_option('lib_title') : $this->get_option('title');
		$this->enabled = $this->get_option('enabled');
		$this->ginger_settings = get_option('woocommerce_ginger_settings');

		$paymentMethod = $this instanceof GingerAdditionalTestingEnvironment ?  $this->id : "";
		$this->gingerClient = WC_Ginger_Clientbuilder::gingerBuildClient($paymentMethod);

		add_action('woocommerce_before_settings_checkout', array($this, 'ginger_checkout_tab_output'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'ginger_update_options_payment_gateways'));
		add_action('updated_option', array($this, 'ginger_check_api_key'), 10, 3);
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'ginger_handle_thankyou'));
		add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'ginger_handle_callback'));
		add_filter('woocommerce_valid_order_statuses_for_payment_complete', array($this, 'ginger_append_processing_order_post_status'));
		add_filter('woocommerce_available_payment_gateways', array($this, 'ginger_filter_gateway_by_currency'), 10);

		if ($this instanceof GingerIdentificationPay) {
			// Create banktransfer order in ginger system when creating an order from the admin panel
			add_action('woocommerce_process_shop_order_meta', array($this, 'process_payment'), 41, 1);
			// Sends instructions for payment in the Order email
			add_action('woocommerce_email_after_order_table', array($this, 'ginger_add_order_email_instructions'), 10, 1);
		}
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

		if (!(int)preg_grep('/' . WC_Ginger_BankConfig::BANK_PREFIX . '/', array_keys($gateways))) return $gateways; //if gateways aren't contain bank's methods, further validations is unnecessary

		$current_currency = get_woocommerce_currency();

		if (!$this->gingerClient) {
			if (!wc_has_notice(__('API key is empty. ' . WC_Ginger_BankConfig::BANK_LABEL . ' payment methods deactivated', "emspay"), 'notice')) {
				wc_add_notice(__('API key is empty. ' . WC_Ginger_BankConfig::BANK_LABEL . ' payment methods deactivated', "emspay"), 'notice');
			}
			foreach ($gateways as $key => $gateway) {
				if ($gateway instanceof GingerAdditionalTestingEnvironment) {
					if (WC_Ginger_Clientbuilder::gingerBuildClient($gateway->id)) continue;
				}
				if (strstr($gateway->id, WC_Ginger_BankConfig::BANK_PREFIX)) unset($gateways[$key]);
			}
			return $gateways;
		}

		try {
			$allowed_currencies = $this->ginger_get_allowed_currencies();
		} catch (Exception $exception) {
			//Unfortunately $exception->getCode() is empty hence we find error code in $exception->getMessage()
			if (strstr($exception->getMessage(), "Unauthorized(401)")) {

				/* translators: %s: the error message */
				$notice = __('API Key is not valid: %s ' . WC_Ginger_BankConfig::BANK_LABEL . ' payment methods deactivated', "emspay");
				if (!wc_has_notice(sprintf($notice, $exception->getMessage()), 'notice')) {
					wc_add_notice(sprintf($notice, $exception->getMessage()), 'notice');
				}
				foreach ($gateways as $key => $gateway) if (strstr($gateway->id, WC_Ginger_BankConfig::BANK_PREFIX)) unset($gateways[$key]);

				return $gateways;
			}

			foreach ($gateways as $gateway) {
				if (!strstr($gateway->id, WC_Ginger_BankConfig::BANK_PREFIX)) continue; //skip woocommerce default payment methods
				$paymentMethod = str_replace(WC_Ginger_BankConfig::BANK_PREFIX . '_', '', $gateway->id); //get payment method name without bank prefix
				$allowed_currencies['payment_methods'][$paymentMethod]['currencies'] = ['EUR']; //create array of currency with one default currency - EUR for each payment method
			}
		}

		$notAvailableGateways = "";
		foreach ($gateways as $key => $gateway) {
			if (!strstr($gateway->id, WC_Ginger_BankConfig::BANK_PREFIX)) continue; //skip woocommerce default payment methods
			$currentMethod = str_replace(WC_Ginger_BankConfig::BANK_PREFIX . '_', '', $gateway->id);
			if (!array_key_exists($currentMethod, $allowed_currencies['payment_methods']) || !$allowed_currencies['payment_methods'][$currentMethod]['currencies']) continue;
			if (!in_array($current_currency, $allowed_currencies['payment_methods'][$currentMethod]['currencies'])) {
				$notAvailableGateways .= $gateway->method_title . "; <br>";
				unset($gateways[$key]);
			}
		}
		if ($notAvailableGateways && !wc_has_notice(__('The following payment methods are not available for selected currency: <br>' . $notAvailableGateways, "emspay"), 'notice')) {
			wc_add_notice(__('The following payment methods are not available for selected currency: <br>' . $notAvailableGateways, "emspay"), 'notice');
		}
		return $gateways;
	}

	public function ginger_get_allowed_currencies()
	{
		$allowed_currencies = $this->ginger_get_cached_currencies();

		if (!$allowed_currencies) {
			$this->ginger_refresh_saved_currency_array();
			$allowed_currencies = $this->ginger_get_cached_currencies();
		}

		return $allowed_currencies;
	}

	public function ginger_get_cached_currencies()
	{
		if (file_exists(__DIR__ . "/../ginger_currency_list.json")) {
			$currencyList = json_decode(file_get_contents(__DIR__ . "/../ginger_currency_list.json"), true);
			if ($currencyList['expired_time'] > time()) return $currencyList['currency_list'];
		}

		return false;
	}

	public function ginger_check_api_key($option_name, $old_value, $new_value)
	{
		if ($option_name == 'woocommerce_ginger_settings' && $this->id == 'ginger') {
			WC_Admin_Notices::remove_notice('ginger-exception-error');
			try {
				#TODO refactor ClientBuilder and add possibility to provide custom api-key, currently api-key is caching and at this moment  get_option('woocommerce_ginger_settings') contains old api-key
				$this->gingerClient = \Ginger\Ginger::createClient(
					WC_Ginger_BankConfig::GINGER_BANK_ENDPOINT,
					$new_value['api_key'],
					($new_value['bundle_cacert'] == 'yes') ?
						[
							CURLOPT_CAINFO => WC_Ginger_Clientbuilder::gingerGetCaCertPath()
						] : []
				);
				$this->ginger_refresh_saved_currency_array();
			} catch (Exception $exception) {
				WC_Admin_Notices::add_custom_notice('ginger-exception-error', $exception->getMessage());
			}
		}
	}


	public function ginger_refresh_saved_currency_array()
	{
		$allowed_currencies = $this->gingerClient->getCurrencyList();
		$currencyListWithExpiredTime = [
			'currency_list' => $allowed_currencies,
			'expired_time' => time() + (60 * 6)
		];
		file_put_contents(__DIR__ . "/../ginger_currency_list.json", json_encode($currencyListWithExpiredTime));
	}

	/**
	 * @return null|void
	 */
	public function payment_fields()
	{
		if (!$this->has_fields) return null;

		if ($this instanceof GingerIssuers) {
			echo '<select name="ginger_ideal_issuer_id">';
			echo '<option value="">' . esc_html__('Choose your bank:', "emspay") . '</option>';
			foreach ($this->gingerClient->getIdealIssuers() as $issuer) {
				echo '<option value="' . $issuer['id'] . '">' . htmlspecialchars($issuer['name']) . '</option>';
			}
			echo '</select>';
		}

		if ($this instanceof GingerCustomerPersonalInformation) {
			echo '<fieldset><legend>' . esc_html__('Additional Information', "emspay") . '</legend >';

			woocommerce_form_field('gender', array(
				'type' => 'select',
				'class' => array('input-text'),
				'options' => array(
					'' => __('Gender', "emspay"),
					'male' => __('Male', "emspay"),
					'female' => __('Female', "emspay"),
				),
				'required' => true
			));

?>
			<select class="dob_select dob_day" name="ginger_afterpay_date_of_birth_day">
				<option value="">
					<?php esc_html_e('Dag', 'emspay'); ?>
				</option>
				<?php
				$day = 1;
				while ($day <= 31) {
					$day_pad = str_pad($day, 2, '0', STR_PAD_LEFT);
					echo '<option value="' . esc_attr($day_pad) . '">' . esc_html($day_pad) . '</option>';
					$day++;
				}
				?>
			</select>
			<select class="dob_select dob_month" name="ginger_afterpay_date_of_birth_month">
				<option value="">
					<?php esc_html_e('Maand', 'emspay'); ?>
				</option>
				<option value="01"><?php esc_html_e('Jan', 'emspay'); ?></option>
				<option value="02"><?php esc_html_e('Feb', 'emspay'); ?></option>
				<option value="03"><?php esc_html_e('Mar', 'emspay'); ?></option>
				<option value="04"><?php esc_html_e('Apr', 'emspay'); ?></option>
				<option value="05"><?php esc_html_e('May', 'emspay'); ?></option>
				<option value="06"><?php esc_html_e('Jun', 'emspay'); ?></option>
				<option value="07"><?php esc_html_e('Jul', 'emspay'); ?></option>
				<option value="08"><?php esc_html_e('Aug', 'emspay'); ?></option>
				<option value="09"><?php esc_html_e('Sep', 'emspay'); ?></option>
				<option value="10"><?php esc_html_e('Oct', 'emspay'); ?></option>
				<option value="11"><?php esc_html_e('Nov', 'emspay'); ?></option>
				<option value="12"><?php esc_html_e('Dec', 'emspay'); ?></option>
			</select>
			<select class="dob_select dob_year" name="ginger_afterpay_date_of_birth_year">
				<option value="">
					<?php esc_html_e('Jaar', 'emspay'); ?>
				</option>
				<?php
				// Select current date and deduct 18 years because of the date limit of using AfterPay.
				$year = date('Y') - 18;
				// Select the oldest year (current year minus 100 years).
				$lowestyear = $year - 82;
				while ($year >= $lowestyear) {
					echo '<option value="' . esc_attr($year) . '">' . esc_html($year) . '</option>';
					$year--;
				}
				?>
			</select>
<?php

			/* translators: %s: the terms and conditions URL */
			$notice = __("I accept <a href='%s' target='_blank'>Terms and Conditions</a>", "emspay");
			woocommerce_form_field('toc', array(
				'type' => 'checkbox',
				'class' => array('input-text'),

				'label' => sprintf(
					$notice,
					(WC_Ginger_Helper::gingerGetBillingCountry() == 'NL' ? static::GINGER_AFTERPAY_TERMS_CONDITION_URL_NL : static::GINGER_AFTERPAY_TERMS_CONDITION_URL_EN)
				),
				'required' => true,
				'id' => 'toc'
			));

			echo "</fieldset>";
		}
	}


	/**
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment($order_id)
	{
		$this->merchant_order_id = $order_id;
		$this->woocommerceOrder = new WC_Order($this->merchant_order_id);

		if ($this->woocommerceOrder->get_payment_method() != $this->id) return false;

		if ($this instanceof GingerIssuers) {
			if (!$this->gingerGetSelectedIssuer()) {
				wc_add_notice(__('Payment Error: You must choose an iDEAL Bank!', "emspay"), 'error');
				return ['result' => 'failure'];
			}
		}

		try {
			$gingerOrder = $this->gingerClient->createOrder($this->gingerGetBuiltOrder());
		} catch (\Exception $exception) {

			/* translators: %s: The error message */
			$notice = __('There was a problem processing your transaction: %s', "emspay");
			wc_add_notice(sprintf($notice, $exception->getMessage()), 'error');
			return [
				'result' => 'failure'
			];
		}

		update_post_meta($this->merchant_order_id, WC_Ginger_BankConfig::BANK_PREFIX . '_order_id', $gingerOrder['id']);

		if ($gingerOrder['status'] == 'error') {
			wc_add_notice(current($gingerOrder['transactions'])['customer_message'], 'error');
			return [
				'result' => 'failure'
			];
		}
		if ($gingerOrder['status'] == 'cancelled') {
			wc_add_notice(
				__('Unfortunately, we can not currently accept your purchase. Please choose another payment option to complete your order. We apologize for the inconvenience.'),
				'error'
			);
			return [
				'result' => 'failure',
				'redirect' => $this->woocommerceOrder->get_cancel_order_url($this->woocommerceOrder)
			];
		}

		if ($this instanceof GingerIdentificationPay) {
			$this->woocommerceOrder->update_status('on-hold', __('Awaiting Bank-Transfer Payment', "emspay"));
			update_post_meta(
				$this->merchant_order_id,
				'bank_reference',
				current($gingerOrder['transactions'])['payment_method_details']['reference']
			);

			return [
				'result' => 'success',
				'redirect' => $this->get_return_url($this->woocommerceOrder)
			];
		}

		if ($this instanceof GingerHostedPaymentPage) {
			$paymentURL = $gingerOrder['order_url']; //in gateway with hosted payment page - payment url must be $gingerOrder['order_url']
		}

		return [
			'result' => 'success',
			'redirect' => $paymentURL ?? current($gingerOrder['transactions'])['payment_url']
		];
	}

	/**
	 * Function ginger_checkout_tab_output
	 */
	public function ginger_checkout_tab_output()
	{
		WC_Admin_Notices::remove_notice('ginger-error');
	}

	/**
	 * Function ginger_update_options_payment_gateways
	 */
	public function ginger_update_options_payment_gateways()
	{
		WC_Admin_Notices::remove_notice('ginger-error');
		if ($this->id !== 'ginger') $this->ginger_validate_currency();
	}

	/**
	 * Function ginger_append_processing_order_post_status
	 * Appended 'processing' order post status to correct status update for 'processing' or 'complemented' by WooCommerce
	 *
	 * @param $statuses
	 * @return mixed
	 */
	public function ginger_append_processing_order_post_status($statuses)
	{
		if (!in_array('processing', $statuses)) {
			$statuses[] = 'processing';
		}

		return $statuses;
	}

	public function ginger_handle_thankyou($order_id)
	{
		WC()->cart->empty_cart();

		if ($this instanceof GingerIdentificationPay) {
			echo $this->gingerIdentificationProcess($order_id);
			return true;
		}

		$gingerOrderIDArray = get_post_custom_values(WC_Ginger_BankConfig::BANK_PREFIX . '_order_id', $order_id);

		if (is_array($gingerOrderIDArray) && $gingerOrderIDArray[0]) {
			$gingerOrder = $this->gingerClient->getOrder($gingerOrderIDArray[0]);
			if ($gingerOrder['status'] == 'processing') {
				echo esc_html__(
					"Your transaction is still being processed. You will be notified when status is updated.",
					"emspay"
				);
			}
		}
	}

	function admin_options()
	{
		if (!$this->enabled && count($this->errors)) {
			echo '<div class="inline error"><p><strong>' . __('Gateway Disabled', "emspay") . '</strong>: '
				. implode('<br/>', $this->errors)
				. '</p></div>';
		}

		echo '<h2>' . esc_html($this->method_title) . '</h2>';
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}

	public function ginger_init_form_fields()
	{
		$this->form_fields = WC_Ginger_Helper::gingerGetFormFields($this);
	}

	public function get_icon()
	{
		return apply_filters('woocommerce_gateway_icon', WC_Ginger_Helper::gingerGetIconSource($this->id), $this->id);
	}

	/**
	 * Function ginger_validate_currency
	 */
	protected function ginger_validate_currency()
	{

		if (!$this->gingerClient) {
			$reason = __('API key is empty. Set API key and try again', "emspay");
			$this->gingerDisabledPaymentMethod($reason);
			return false;
		}

		try {
			$payment_methods_currencies = $this->gingerClient->getCurrencyList();
		} catch (Exception $exception) {
			$this->gingerDisabledPaymentMethod($exception->getMessage());
			return false;
		}

		if (!$this->gingerIsGatewayCurrencySupported($payment_methods_currencies)) {

			/* translators: %s: The currency %s: webshop name */
			$notice = __('Current shop currency %1$s not supported by %2$s.', "emspay");
			$reason = sprintf(
				$notice,
				get_woocommerce_currency(),
				$this->get_option('title')
			);
			$this->gingerDisabledPaymentMethod($reason);
			return false;
		}
		return true;
	}

	/**
	 * Function gingerIsGatewayCurrencySupported
	 *
	 * @param $payment_methods_currencies
	 * @return bool
	 */
	protected function gingerIsGatewayCurrencySupported($payment_methods_currencies)
	{
		$currentMethod = strtr($this->id, [WC_Ginger_BankConfig::BANK_PREFIX . '_' => '']);
		if (empty($payment_methods_currencies['payment_methods'][$currentMethod]['currencies'])) {
			return true;
		}
		return in_array(get_woocommerce_currency(), $payment_methods_currencies['payment_methods'][$currentMethod]['currencies']);
	}

	/**
	 * Function gingerDisabledPaymentMethod
	 *
	 * @param $reason
	 */
	public function gingerDisabledPaymentMethod($reason)
	{
		$this->enabled = false;
		$this->update_option('enabled', false);
	}

	/**
	 * Adds instructions for order emails
	 *
	 * @param $order
	 */
	public function ginger_add_order_email_instructions($order)
	{

		$payment_method = $order->get_payment_method();

		if ($payment_method == WC_Ginger_BankConfig::BANK_PREFIX . '_bank-transfer') {
			echo $this->gingerIdentificationProcess($order->get_id());
		}
	}

	/**
	 * Function return payment details
	 * @param $order_id
	 * @return string
	 */
	public function gingerIdentificationProcess($order_id): string
	{
		if (!$this->gingerClient) return true;
		$gingerOrder = $this->gingerClient->getOrder(get_post_custom_values(WC_Ginger_BankConfig::BANK_PREFIX . '_order_id', $order_id)[0]);

		$gingerOrderIBAN = current($gingerOrder['transactions'])['payment_method_details']['creditor_iban'];
		$gingerOrderReference = current($gingerOrder['transactions'])['payment_method_details']['reference'];
		$gingerOrderBIC = current($gingerOrder['transactions'])['payment_method_details']['creditor_bic'];
		$gingerOrderHolderName = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_name'];
		$gingerOrderHolderCity = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_city'];

		return esc_html__("Please use the following payment information:", "emspay")
			. "<br/>"
			. esc_html__("Bank Reference:", "emspay") . ' ' . $gingerOrderReference
			. "<br/>"
			. esc_html__("IBAN:", "emspay") . ' ' . $gingerOrderIBAN
			. "<br/>"
			. esc_html__("BIC:", "emspay") . ' ' . $gingerOrderBIC
			. "<br/>"
			. esc_html__("Account Holder:", "emspay") . ' ' . $gingerOrderHolderName
			. "<br/>"
			. esc_html__("Residence:", "emspay") . ' ' . $gingerOrderHolderCity
			. "<br/><br/>";
	}
}
