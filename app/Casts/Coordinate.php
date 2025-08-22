<?php

namespace App\Casts;

use App\ValueObjects\Coordinate as ValueObjectsCoordinate;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use Throwable;

/**
 * @implements CastsAttributes<ValueObjectsCoordinate, array<string, float>>
 */
class Coordinate implements CastsAttributes
{
    /**
     * @return ValueObjectsCoordinate|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (! array_key_exists('lat', $attributes) || ! array_key_exists('lng', $attributes)) {
            return null;
        }

        if ($attributes['lat'] === null || $attributes['lng'] === null) {
            return null;
        }

        return new ValueObjectsCoordinate(
            lat: $attributes['lat'],
            lng: $attributes['lng']
        );
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return [
                'lat' => null,
                'lng' => null,
            ];
        }

        if (! $value instanceof ValueObjectsCoordinate) {
            try {
                $value = ValueObjectsCoordinate::fromArray($value);
            } catch (Throwable $e) {
                throw new InvalidArgumentException('The given value is not an Coordinate instance and cannot be casted to a valueobject', 0, $e);
            }
        }

        return [
            'lat' => $value->lat,
            'lng' => $value->lng,
        ];
    }
}
