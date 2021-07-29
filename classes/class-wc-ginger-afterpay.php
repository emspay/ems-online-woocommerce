<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_AfterPay extends WC_Ginger_BankGateway implements
    GingerOrderLines,
    GingerCustomerPersonalInformation,
    GingerAdditionalTestingEnvironment,
    GingerCountryValidation
{
    public function __construct()
    {
        $this->id = WC_Ginger_BankConfig::BANK_PREFIX.'_afterpay';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = __('AfterPay - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);
        $this->method_description = __('AfterPay - '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX);

        parent::__construct();
    }
}
