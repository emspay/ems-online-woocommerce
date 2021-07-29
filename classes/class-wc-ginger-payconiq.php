<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_Payconiq extends WC_Ginger_BankGateway
{

    public function __construct()
    {
        $this->id = WC_Ginger_BankConfig::BANK_PREFIX.'_payconiq';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title =  __('PAYCONIQ - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);
        $this->method_description = __('PAYCONIQ - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);

        parent::__construct();
    }
}

