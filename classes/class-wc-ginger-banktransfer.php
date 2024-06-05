<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_Ginger_Banktransfer extends WC_Ginger_BankGateway implements GingerIdentificationPay
{
	public function __construct()
	{
		$this->id = WC_Ginger_BankConfig::BANK_PREFIX . '_bank-transfer';
		$this->icon = false;
		$this->has_fields = false;
		$this->method_title = __('Bank Transfer - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");
		$this->method_description = __('Bank Transfer - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");

		parent::__construct();
	}
}
