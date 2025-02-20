<?php

namespace App\Casts;

use App\ValueObjects\Polygon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class PolygonCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return $value ? Polygon::fromJson($value) : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return [
                $key => null,
            ];
        }

        if (is_array($value)) {
            $value = new Polygon($value);
        }

        if (!$value instanceof Polygon) {
            throw new InvalidArgumentException('The given value is not a Polygon instance.');
        }

        return [
            $key => $value->toJson(),
        ];
    }
} 