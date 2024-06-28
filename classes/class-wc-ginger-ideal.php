<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_Ginger_Ideal extends WC_Ginger_BankGateway
{

	public function __construct()
	{
		$this->id = WC_Ginger_BankConfig::BANK_PREFIX . '_ideal';
		$this->icon = false;
		$this->has_fields = true;
		$this->method_title = __('iDEAL - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");
		$this->method_description = __('iDEAL - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");

		parent::__construct();
	}
}
