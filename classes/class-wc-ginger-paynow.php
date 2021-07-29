<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_PayNow extends WC_Ginger_BankGateway implements GingerHostedPaymentPage
{
    public function __construct()
    {
        $this->id = WC_Ginger_BankConfig::BANK_PREFIX.'_pay-now';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Pay now - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);
        $this->method_description = __('Pay now - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);

        parent::__construct();
    }
}
