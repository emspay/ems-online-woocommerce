<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_Banktransfer extends WC_Emspay_Gateway
{

	/**
	 *  EMS Online bank transfer details
	 */
	const EMS_BIC = 'ABNANL2A';
	const EMS_IBAN = 'NL79ABNA0842577610';
	const EMS_HOLDER = 'THIRD PARTY FUNDS EMS';
	const EMS_RESIDENCE = 'Amsterdam';

    /**
     * WC_Emspay_Banktransfer constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_bank-transfer';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Banktransfer - EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('Banktransfer - EMS Online', WC_Emspay_Helper::DOMAIN);

        parent::__construct();

        // Create banktransfer order in ginger system when creating an order from the admin panel
        add_action('woocommerce_process_shop_order_meta', array($this, 'process_payment'), 41, 1);

        // Sends instructions for payment in the Order email
        add_action( 'woocommerce_email_after_order_table', array($this, 'add_order_email_instructions'), 10, 1 );

    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        if( $order->get_payment_method() != $this->id ) {
              return false;
        }

        try {
            $emsOrder = $this->ems->createOrder(array_filter([
                 'amount' => WC_Emspay_Helper::gerOrderTotalInCents($order),
                 'currency' => WC_Emspay_Helper::getCurrency(),
                 'transactions' => [
                     [
                         'payment_method' => str_replace('emspay_', '', $this->id)
                     ]
                 ],
                 'merchant_order_id' => (string) $order_id,
                 'description' => WC_Emspay_Helper::getOrderDescription($order_id),
                 'return_url' => WC_Emspay_Helper::getReturnUrl(),
                 'customer' => WC_Emspay_Helper::getCustomerInfo($order),
                 'extra' => ['plugin' => EMSPAY_PLUGIN_VERSION],
                 'webhook_url' => WC_Emspay_Helper::getWebhookUrl()
            ]));
        } catch (\Exception $exception) {
            wc_add_notice(sprintf(__('There was a problem processing your transaction: %s', WC_Emspay_Helper::DOMAIN), $exception->getMessage()), 'error');
            return [
                'result' => 'failure'
            ];
        }

        $bank_reference = !empty(current($emsOrder['transactions'])) ?
            current($emsOrder['transactions'])['payment_method_details']['reference'] : null;

        update_post_meta($order_id, 'bank_reference', $bank_reference);
        update_post_meta($order_id, 'ems_order_id', $emsOrder['id']);

        $order->update_status('on-hold', __('Awaiting Bank-Transfer Payment', WC_Emspay_Helper::DOMAIN));

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }

    /**
     * @param $order_id
     */
    public function handle_thankyou($order_id)
    {
        WC()->cart->empty_cart();

        echo $this->get_instructions($order_id);
    }

    /**
     * Adds instructions for order emails
     *
     * @param $order
     */
    public function add_order_email_instructions($order) {

        $payment_method = $order->get_payment_method();

        if( $payment_method == $this->id ) {
            echo $this->get_instructions($order->get_id());
        }
    }

    /**
     * @param $order_id
     * @return string
     */
    public function get_instructions($order_id){

        $reference = get_post_custom_values('bank_reference', $order_id);

        return __("Please use the following payment information:", WC_Emspay_Helper::DOMAIN)
            . "<br/>"
            . __("Bank Reference:", WC_Emspay_Helper::DOMAIN).' '.$reference[0]
            . "<br/>"
            . __("IBAN:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_IBAN
            . "<br/>"
            . __("BIC:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_BIC
            . "<br/>"
            . __("Account Holder:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_HOLDER
            . "<br/>"
            . __("Residence:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_RESIDENCE
            . "<br/><br/>";
    }
}
