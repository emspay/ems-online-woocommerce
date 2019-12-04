<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Emspay_AfterPay extends WC_Emspay_Gateway
{
    const TERMS_CONDITION_URL_NL = 'https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden';
    const TERMS_CONDITION_URL_BE = 'https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden';

    /**
     * WC_Emspay_AfterPay constructor.
     */
    public function __construct()
    {
        $this->id = 'emspay_afterpay';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = __('EMS Online : AfterPay', WC_Emspay_Helper::DOMAIN);
        $this->method_description = __('EMS Online : AfterPay', WC_Emspay_Helper::DOMAIN);

        parent::__construct();
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $emsOrder = $this->ems->createOrder([
            'amount' => WC_Emspay_Helper::gerOrderTotalInCents($order),
            'currency' => WC_Emspay_Helper::getCurrency(),
            'transactions' => [
                [
                    'payment_method' => str_replace('emspay_', '', $this->id)
                ]
            ],
            'merchant_order_id' => $order_id,
            'description' => WC_Emspay_Helper::getOrderDescription($order_id),
            'return_url' => WC_Emspay_Helper::getReturnUrl(),
            'customer' => WC_Emspay_Helper::getCustomerInfo($order),
            'extra' => ['plugin' => EMSPAY_PLUGIN_VERSION],
            'webhook_url' => WC_Emspay_Helper::getWebhookUrl($this),
            'order_lines' => WC_Emspay_Helper::getOrderLines($order)
        ]);

        update_post_meta($order_id, 'ems_order_id', $emsOrder['id']);

        if ($emsOrder['status'] == 'error') {
            wc_add_notice(current($emsOrder['transactions'])['reason'], 'error');
            return [
                'result' => 'failure',
            ];
        } elseif ($emsOrder['status'] == 'cancelled') {
            wc_add_notice(
                __('Unfortunately, we can not currently accept your purchase with AfterPay. Please choose another payment option to complete your order. We apologize for the inconvenience.', WC_Emspay_Helper::DOMAIN),
                'error'
            );
            return [
                'result' => 'failure',
                'redirect' => $order->get_cancel_order_url($order)
            ];
        }

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

        echo "<p><b>".__('Your payment using AfterPay is successful.', WC_Emspay_Helper::DOMAIN)."</b></p>";
        echo "<p>".__('The invoice will be sent to your email.', WC_Emspay_Helper::DOMAIN)."</p>";
    }

    /**
     * @return null|void
     */
    public function payment_fields()
    {
        if (!$this->has_fields) {
            return null;
        }

        echo '<fieldset><legend>'.__('Additional Information', WC_Emspay_Helper::DOMAIN).'</legend >';

        woocommerce_form_field('gender', array(
            'type' => 'select',
            'class' => array('input-text'),
            'label' => __('Gender:', WC_Emspay_Helper::DOMAIN),
            'options' => array(
                '' => '',
                'male' => __('Male', WC_Emspay_Helper::DOMAIN),
                'female' => __('Female', WC_Emspay_Helper::DOMAIN),
            ),
            'required' => true
        ));

        ?>
        <select class="dob_select dob_day" name="<?php echo esc_attr( $this->id ); ?>_date_of_birth_day">
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
        <select class="dob_select dob_month" name="<?php echo esc_attr( $this->id ); ?>_date_of_birth_month">
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
        <select class="dob_select dob_year" name="<?php echo esc_attr( $this->id ); ?>_date_of_birth_year">
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
                __("I accept <a href='%s' target='_blank'>Terms and Conditions</a>", WC_Emspay_Helper::DOMAIN),
                (WC()->customer->get_billing_country() == 'NL'?static::TERMS_CONDITION_URL_NL:static::TERMS_CONDITION_URL_BE)
            ),
            'required' => true
        ));

        echo "</fieldset>";
    }
}
