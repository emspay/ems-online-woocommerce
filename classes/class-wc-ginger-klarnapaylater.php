<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_Ginger_KlarnaPayLater extends WC_Ginger_BankGateway implements GingerAdditionalTestingEnvironment
{

	public function __construct()
	{
		$this->id = WC_Ginger_BankConfig::BANK_PREFIX . '_klarna-pay-later';
		$this->icon = false;
		$this->has_fields = false;
		$this->method_title = __('Klarna Pay Later - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");
		$this->method_description = __('Klarna Pay Later - ' . WC_Ginger_BankConfig::BANK_LABEL, "emspay");

		parent::__construct();
	}
}
