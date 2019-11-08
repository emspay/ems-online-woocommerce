<?php

namespace GingerPayments\Payment\Order\Transaction\PaymentMethodDetails;

use GingerPayments\Payment\Order\Transaction\PaymentMethodDetails;

final class PayNowPaymentMethodDetails implements PaymentMethodDetails
{
    /**
     * @param array $details
     * @return PayNowPaymentMethodDetails
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
