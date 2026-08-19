<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Polygon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** @implements CastsAttributes<Polygon|null, Polygon|array<string, mixed>|null> */
class PolygonCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Polygon
    {
        return $value ? Polygon::fromJson($value) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value === null) {
            return [
                $key => null,
            ];
        }

        if (is_array($value)) {
            if (array_key_exists('coordinates', $value)) {
                $value = $value['coordinates'];
            }
            $value = new Polygon($value);
        }

        if (! $value instanceof Polygon) {
            throw new InvalidArgumentException('The given value is not a Polygon instance.');
        }

        return [
            $key => $value->toJson(),
        ];
    }
}
