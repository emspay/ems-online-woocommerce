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
     * WC_emspay_banktransfer constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_banktransfer';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Banktransfer - EMS Online', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('Banktransfer - EMS Online', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $emsOrder = $this->ems->createSepaOrder(
            WC_Emspay_Helper::gerOrderTotalInCents($order),          // Amount in cents
            WC_Emspay_Helper::getCurrency(),                         // Currency
            [],                                                      // Payment method details
            WC_Emspay_Helper::getOrderDescription($order_id),        // Description
            $order_id,                                               // Merchant id
            WC_Emspay_Helper::getReturnUrl(),                        // Return url,
            null,                                                    // expiration
            WC_Emspay_Helper::getCustomerInfo($order),               // customer
            ['plugin' => EMSPAY_PLUGIN_VERSION],                     // extra information
            WC_Emspay_Helper::getWebhookUrl($this)                   // webhook_url
        );

        $bank_reference = $emsOrder->Transactions()->current()->paymentMethodDetails()->reference()->toString();

        update_post_meta($order_id, 'bank_reference', $bank_reference);
        update_post_meta($order_id, 'ems_order_id', $emsOrder->getId());

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

        $reference = get_post_custom_values('bank_reference', $order_id);

        echo __("Please use the following payment information:", WC_Emspay_Helper::DOMAIN);
        echo "<br/>";
        echo __("Bank Reference:", WC_Emspay_Helper::DOMAIN).' '.$reference[0];
        echo "<br/>";
        echo __("IBAN:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_IBAN;
        echo "<br/>";
        echo __("BIC:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_BIC;
        echo "<br/>";
        echo __("Account Holder:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_HOLDER;
        echo "<br/>";
        echo __("Residence:", WC_Emspay_Helper::DOMAIN).' '.static::EMS_RESIDENCE;
        echo "<br/><br/>";
    }
}
