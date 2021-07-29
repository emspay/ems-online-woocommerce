<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_KlarnaPayNow extends WC_Ginger_BankGateway
{

    public function __construct()
    {
        $this->id = WC_Ginger_BankConfig::BANK_PREFIX.'_klarna-pay-now';
        $this->icon = false;
        $this->has_fields = false;
        $this->method_title = __('Klarna Pay Now - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);
        $this->method_description = __('Klarna Pay Now - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);

        parent::__construct();
    }
}

