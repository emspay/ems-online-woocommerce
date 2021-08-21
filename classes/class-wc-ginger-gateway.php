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

        add_action( 'woocommerce_before_settings_checkout', array( $this, 'ginger_checkout_tab_output' ) );
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'ginger_update_options_payment_gateways'));
        add_action('woocommerce_thankyou_'.$this->id, array($this, 'ginger_handle_thankyou'));
        add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'ginger_handle_callback'));
        add_filter('woocommerce_valid_order_statuses_for_payment_complete', array($this, 'ginger_append_processing_order_post_status'));

        if($this instanceof GingerIdentificationPay)
        {
            // Create banktransfer order in ginger system when creating an order from the admin panel
            add_action('woocommerce_process_shop_order_meta', array($this, 'process_payment'), 41, 1);
            // Sends instructions for payment in the Order email
            add_action( 'woocommerce_email_after_order_table', array($this, 'ginger_add_order_email_instructions'), 10, 1 );
        }

    }


    /**
     * @return null|void
     */
    public function payment_fields()
    {
        if (!$this->has_fields) return null;

        if($this instanceof GingerIssuers)
        {
            echo '<select name="ginger_ideal_issuer_id">';
            echo '<option value="">'.esc_html__('Choose your bank:', WC_Ginger_BankConfig::BANK_PREFIX).'</option>';
            foreach ($this->gingerClient->getIdealIssuers() AS $issuer)
            {
                echo '<option value="'.$issuer['id'].'">'.htmlspecialchars($issuer['name']).'</option>';
            }
            echo '</select>';
        }

        if($this instanceof GingerCustomerPersonalInformation)
        {
            echo '<fieldset><legend>'.esc_html__('Additional Information', WC_Ginger_BankConfig::BANK_PREFIX).'</legend >';

            woocommerce_form_field('gender', array(
                'type' => 'select',
                'class' => array('input-text'),
                'label' => __('Gender:', WC_Ginger_BankConfig::BANK_PREFIX),
                'options' => array(
                    '' => '',
                    'male' => __('Male', WC_Ginger_BankConfig::BANK_PREFIX),
                    'female' => __('Female', WC_Ginger_BankConfig::BANK_PREFIX),
                ),
                'required' => true
            ));

            ?>
            <select class="dob_select dob_day" name="ginger_afterpay_date_of_birth_day">
                <option value="">
                    <?php esc_html_e( 'Dag', 'afterpay' ); ?>
                </option>
                <?php
                $day = 1;
                while ( $day <= 31 ) {
                    $day_pad = str_pad( $day, 2, '0', STR_PAD_LEFT );
                    echo '<option value="' . esc_attr( $day_pad ) . '">' . esc_html( $day_pad ) . '</option>';
                    $day++;
                }
                ?>
            </select>
            <select class="dob_select dob_month" name="ginger_afterpay_date_of_birth_month">
                <option value="">
                    <?php esc_html_e( 'Maand', 'afterpay' ); ?>
                </option>
                <option value="01"><?php esc_html_e( 'Jan', 'afterpay' ); ?></option>
                <option value="02"><?php esc_html_e( 'Feb', 'afterpay' ); ?></option>
                <option value="03"><?php esc_html_e( 'Mar', 'afterpay' ); ?></option>
                <option value="04"><?php esc_html_e( 'Apr', 'afterpay' ); ?></option>
                <option value="05"><?php esc_html_e( 'May', 'afterpay' ); ?></option>
                <option value="06"><?php esc_html_e( 'Jun', 'afterpay' ); ?></option>
                <option value="07"><?php esc_html_e( 'Jul', 'afterpay' ); ?></option>
                <option value="08"><?php esc_html_e( 'Aug', 'afterpay' ); ?></option>
                <option value="09"><?php esc_html_e( 'Sep', 'afterpay' ); ?></option>
                <option value="10"><?php esc_html_e( 'Oct', 'afterpay' ); ?></option>
                <option value="11"><?php esc_html_e( 'Nov', 'afterpay' ); ?></option>
                <option value="12"><?php esc_html_e( 'Dec', 'afterpay' ); ?></option>
            </select>
            <select class="dob_select dob_year" name="ginger_afterpay_date_of_birth_year">
                <option value="">
                    <?php esc_html_e( 'Jaar', 'afterpay' ); ?>
                </option>
                <?php
                // Select current date and deduct 18 years because of the date limit of using AfterPay.
                $year = date( 'Y' ) - 18;
                // Select the oldest year (current year minus 100 years).
                $lowestyear = $year - 82;
                while ( $year >= $lowestyear ) {
                    echo '<option value="' . esc_attr( $year ) . '">' . esc_html( $year ) . '</option>';
                    $year--;
                }
                ?>
            </select>
            <?php

            woocommerce_form_field('toc', array(
                'type' => 'checkbox',
                'class' => array('input-text'),
                'label' => sprintf(
                    __("I accept <a href='%s' target='_blank'>Terms and Conditions</a>", WC_Ginger_BankConfig::BANK_PREFIX),
                    (WC_Ginger_Helper::gingerGetBillingCountry() == 'NL'?WC_Ginger_Helper::GINGER_AFTERPAY_TERMS_CONDITION_URL_NL: WC_Ginger_Helper::GINGER_AFTERPAY_TERMS_CONDITION_URL_BE)
                ),
                'required' => true
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

        if($this->woocommerceOrder->get_payment_method() != $this->id) return false;

        if ($this instanceof GingerIssuers)
        {
            if (!$this->gingerGetSelectedIssuer())
            {
                wc_add_notice(__('Payment Error: You must choose an iDEAL Bank!', WC_Ginger_BankConfig::BANK_PREFIX), 'error');
                return ['result' => 'failure'];
            }
        }

        try {
            $gingerOrder = $this->gingerClient->createOrder($this->gingerGetBuiltOrder());
        } catch (\Exception $exception) {
            wc_add_notice(sprintf(__('There was a problem processing your transaction: %s', WC_Ginger_BankConfig::BANK_PREFIX), $exception->getMessage()), 'error');
            return [
                'result' => 'failure'
            ];
        }

        update_post_meta($this->merchant_order_id, WC_Ginger_BankConfig::BANK_PREFIX.'_order_id', $gingerOrder['id']);

        if ($gingerOrder['status'] == 'error')
        {
            wc_add_notice(current($gingerOrder['transactions'])['customer_message'], 'error');
            return [
                'result' => 'failure'
            ];
        }
        if($gingerOrder['status'] == 'cancelled')
        {
            wc_add_notice(
                __('Unfortunately, we can not currently accept your purchase. Please choose another payment option to complete your order. We apologize for the inconvenience.'),
                'error'
            );
            return [
                'result' => 'failure',
                'redirect' => $this->woocommerceOrder->get_cancel_order_url($this->woocommerceOrder)
            ];
        }

        if($this instanceof GingerIdentificationPay)
        {
            $this->woocommerceOrder->update_status('on-hold', __('Awaiting Bank-Transfer Payment', WC_Ginger_BankConfig::BANK_PREFIX));
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

        if ($this instanceof GingerHostedPaymentPage)
        {
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
        if($this->id !== 'ginger') $this->ginger_validate_currency();

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
        if(! in_array('processing', $statuses)) {
            $statuses[] = 'processing';
        }

        return $statuses;
    }

    public function ginger_handle_thankyou($order_id)
    {
        WC()->cart->empty_cart();

        if ($this instanceof GingerIdentificationPay)
        {
            echo $this->gingerIdentificationProcess($order_id);
            return true;
        }

        $gingerOrderIDArray = get_post_custom_values(WC_Ginger_BankConfig::BANK_PREFIX.'_order_id', $order_id);

        if (is_array($gingerOrderIDArray) && $gingerOrderIDArray[0])
        {
            $gingerOrder = $this->gingerClient->getOrder($gingerOrderIDArray[0]);
            if ($gingerOrder['status'] == 'processing')
            {
                echo esc_html__(
                    "Your transaction is still being processed. You will be notified when status is updated.",
                    WC_Ginger_BankConfig::BANK_PREFIX
                );
            }
        }
    }

    function admin_options()
    {
        if (!$this->enabled && count($this->errors)) {
            echo '<div class="inline error"><p><strong>' . __('Gateway Disabled', WC_Ginger_BankConfig::BANK_PREFIX) . '</strong>: '
                . implode('<br/>', $this->errors)
                . '</p></div>';
        }

        echo '<h2>'.esc_html($this->method_title).'</h2>';
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

        if(!$this->gingerClient)
        {
            $reason = __( 'API key is empty. Set API key and try again', WC_Ginger_BankConfig::BANK_PREFIX );
            $this->gingerDisabledPaymentMethod($reason);
            return false;
        }

        try {
            $payment_methods_currencies = $this->gingerClient->getCurrencyList();
        } catch (Exception $exception) {
            $this->gingerDisabledPaymentMethod($exception->getMessage());
            return false;
        }

        if (!$this->gingerIsGatewayCurrencySupported($payment_methods_currencies))
        {
            $reason = sprintf(
                __( 'Current shop currency %s not supported by %s.', WC_Ginger_BankConfig::BANK_PREFIX ),
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
    protected function gingerIsGatewayCurrencySupported ($payment_methods_currencies)
    {
        $currentMethod = strtr($this->id, [WC_Ginger_BankConfig::BANK_PREFIX.'_' => '']);
        if(empty($payment_methods_currencies['payment_methods'][$currentMethod]['currencies'])) {
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
        WC_Admin_Notices::add_custom_notice('ginger-error', $reason);
    }

    /**
     * Adds instructions for order emails
     *
     * @param $order
     */
    public function ginger_add_order_email_instructions($order) {

        $payment_method = $order->get_payment_method();

        if( $payment_method == WC_Ginger_BankConfig::BANK_PREFIX . '_bank-transfer')
        {
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
        $gingerOrder = $this->gingerClient->getOrder(get_post_custom_values(WC_Ginger_BankConfig::BANK_PREFIX.'_order_id',$order_id)[0]);
        $paymentReference = get_post_custom_values('bank_reference',$order_id)[0];

        $gingerOrderIBAN = current($gingerOrder['transactions'])['payment_method_details']['creditor_iban'];
        $gingerOrderBIC = current($gingerOrder['transactions'])['payment_method_details']['creditor_bic'];
        $gingerOrderHolderName = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_name'];
        $gingerOrderHolderCity = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_city'];

        return esc_html__("Please use the following payment information:", WC_Ginger_BankConfig::BANK_PREFIX)
            . "<br/>"
            . esc_html__("Bank Reference:", WC_Ginger_BankConfig::BANK_PREFIX).' '.$paymentReference
            . "<br/>"
            . esc_html__("IBAN:", WC_Ginger_BankConfig::BANK_PREFIX).' '.$gingerOrderIBAN
            . "<br/>"
            . esc_html__("BIC:", WC_Ginger_BankConfig::BANK_PREFIX).' '.$gingerOrderBIC
            . "<br/>"
            . esc_html__("Account Holder:", WC_Ginger_BankConfig::BANK_PREFIX).' '.$gingerOrderHolderName
            . "<br/>"
            . esc_html__("Residence:", WC_Ginger_BankConfig::BANK_PREFIX).' '.$gingerOrderHolderCity
            . "<br/><br/>";
    }
}
