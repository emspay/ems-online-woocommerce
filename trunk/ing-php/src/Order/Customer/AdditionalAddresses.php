<?php

namespace GingerPayments\Payment\Order\Customer;

use Assert\Assertion as Guard;

final class AdditionalAddresses implements \Iterator
{
    /**
     * @var AdditionalAddresses[]
     */
    private $additionalAddresses;

    /**
     * @return AdditionalAddresses
     */
    public static function create()
    {
        return new static([]);
    }

    /**
     * @param array $additionalAddresses
     * @return AdditionalAddresses
     */
    public static function fromArray(array $additionalAddresses)
    {
        return new static(
            array_map(
                function ($additionalAddress) {
                    return AdditionalAddress::fromArray($additionalAddress);
                },
                $additionalAddresses
            )
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_map(
            function (AdditionalAddress $additionalAddress) {
                return $additionalAddress->toArray();
            },
            $this->additionalAddresses
        );
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->additionalAddresses);
    }

    public function next()
    {
        return next($this->additionalAddresses);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->additionalAddresses);
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        $key = key($this->additionalAddresses);
        return ($key !== null && $key !== false);
    }

    public function rewind()
    {
        reset($this->additionalAddresses);
    }

    /**
     * @param array $additionalAddresses
     */
    private function __construct(array $additionalAddresses = [])
    {
        Guard::allIsInstanceOf($additionalAddresses, 'GingerPayments\Payment\Order\Customer\AdditionalAddress');

        $this->additionalAddresses = $additionalAddresses;
    }
}
