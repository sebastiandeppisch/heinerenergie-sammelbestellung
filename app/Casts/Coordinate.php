<?php

namespace App\Casts;

use App\ValueObjects\Coordinate as ValueObjectsCoordinate;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class Coordinate implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($attributes['lat'] === null || $attributes['long'] === null) {
            return null;
        }

        return new ValueObjectsCoordinate(
            lat: $attributes['lat'],
            long: $attributes['long']
        );
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return [
                'lat' => null,
                'long' => null,
            ];
        }

        if (! $value instanceof ValueObjectsCoordinate) {
            throw new InvalidArgumentException('The given value is not an Coordinate instance.');
        }

        return [
            'lat' => $value->lat,
            'long' => $value->long,
        ];
    }
}
