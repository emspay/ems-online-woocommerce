<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_Sofort extends WC_Ginger_BankGateway
{
    public function __construct()
    {
        $this->id = WC_Ginger_BankConfig::BANK_PREFIX.'_sofort';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Sofort - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);
        $this->method_description = __('Sofort - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);

        parent::__construct();
    }
}
