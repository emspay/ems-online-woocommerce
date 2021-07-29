<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_Bancontact extends WC_Ginger_BankGateway
{
    public function __construct()
    {
        $this->id = WC_Ginger_BankConfig::BANK_PREFIX.'_bancontact';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Bancontact - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);
        $this->method_description = __('Bancontact - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);

        parent::__construct();
    }
}
