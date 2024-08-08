<?php

namespace App\Casts;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use App\ValueObjects\Address as AddressValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Address implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if($attributes['street'] === null || $attributes['streetNumber'] === null || $attributes['zip'] === null || $attributes['city'] === null) {
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
        if(!$value instanceof AddressValueObject) {
            throw new InvalidArgumentException('The given value is not an Address instance.');
        }
        
        return [
            'street' => $value->street,
            'streetNumber' => $value->streetNumber,
            'zip' => $value->zip,
            'city' => $value->city,
        ];
    }
    
}
