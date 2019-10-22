<?php

namespace GingerPayments\Payment\Order\Transaction\PaymentMethodDetails;

use GingerPayments\Payment\Order\Transaction\PaymentMethodDetails;

final class ApplePayPaymentMethodDetails implements PaymentMethodDetails
{
	/**
	 * @param array $details
	 * @return ApplePayPaymentMethodDetails
	 */
	public static function fromArray(array $details)
	{
		return new static();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return [];
	}
}
