<?php

namespace App\Casts;

use App\ValueObjects\Address as AddressValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class Address implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($attributes['street'] === null || $attributes['street_number'] === null || $attributes['zip'] === null || $attributes['city'] === null) {
            return null;
        }

        return new AddressValueObject(
            street: $attributes['street'],
            street_number: $attributes['street_number'],
            zip: $attributes['zip'],
            city: $attributes['city']
        );
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (! $value instanceof AddressValueObject) {
            $value = $this->get($model, $key, $value, $value);

            if (! $value instanceof AddressValueObject) {
                throw new InvalidArgumentException('The given value is not an Address instance and can not be cast to an AddressValueObject');
            }
        }

        return [
            'street' => $value->street,
            'street_number' => $value->street_number,
            'zip' => $value->zip,
            'city' => $value->city,
        ];
    }
}
