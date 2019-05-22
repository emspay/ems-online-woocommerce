<?php

namespace GingerPayments\Payment\Order\Customer;

use GingerPayments\Payment\Order\Customer\AddressType;
use GingerPayments\Payment\Order\Customer\Address;
use GingerPayments\Payment\Order\Customer\Country;

final class AdditionalAddress
{
    /**
     * @var AddressType|null
     */
    private $addressType;

    /**
     * @var Address|null
     */
    private $address;

    /**
     * @var Country|null
     */
    private $country;

    /**
     * @param array $details
     * @return Customer
     */
    public static function fromArray(array $details)
    {
        return new static(
            array_key_exists('address_type', $details) ? AddressType::fromString($details['address_type']) : null,
            array_key_exists('address', $details) ? Address::fromString($details['address']) : null,
            array_key_exists('country', $details) ? Country::fromString($details['country']) : null
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'address_type' => ($this->addressType() !== null) ? $this->addressType()->toString() : null,
            'address' => ($this->address() !== null) ? $this->address()->toString() : null,
            'country' => ($this->country() !== null) ? $this->country()->toString() : null,
        ];
    }

    /**
     * @return AddressType|null
     */
    public function addressType()
    {
        return $this->addressType;
    }

    /**
     * @return Address|null
     */
    public function address()
    {
        return $this->address;
    }

    /**
     * @return Country|null
     */
    public function country()
    {
        return $this->country;
    }

    /**
     * @param AddressType $addressType
     * @param Address $address
     * @param Country $country
     */
    private function __construct(
        AddressType $addressType = null,
        Address $address = null,
        Country $country = null
    ) {
        $this->addressType = $addressType;
        $this->address = $address;
        $this->country = $country;
    }
}
