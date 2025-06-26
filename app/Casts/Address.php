<?php

namespace App\Casts;

use App\ValueObjects\Address as AddressValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class Address implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($attributes['street'] === null || $attributes['streetNumber'] === null || $attributes['zip'] === null || $attributes['city'] === null) {
            return null;
        }

        return new AddressValueObject(
            street: $attributes['street'],
            streetNumber: $attributes['streetNumber'],
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
            'streetNumber' => $value->streetNumber,
            'zip' => $value->zip,
            'city' => $value->city,
        ];
    }
}
