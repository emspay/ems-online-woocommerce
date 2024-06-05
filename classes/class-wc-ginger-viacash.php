<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_Ginger_ViaCash extends WC_Ginger_BankGateway
{
	public function __construct()
	{
		$this->id = WC_Ginger_BankConfig::BANK_PREFIX . '_viacash';
		$this->icon = false;
		$this->has_fields = false;
		$this->method_title = __('ViaCash - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");
		$this->method_description = __('ViaCash - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");

		parent::__construct();
	}
}
