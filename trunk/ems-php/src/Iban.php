<?php

namespace GingerPayments\Payment;

use GingerPayments\Payment\Common\StringBasedValueObject;

final class Iban
{
    use StringBasedValueObject;

    /**
     * @param string $value
     */
    private function __construct($value)
    {
        $this->value = $value;
    }
}
