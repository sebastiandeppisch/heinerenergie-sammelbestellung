<?php

namespace App\ValueObjects;

use App\Casts\Coordinate as CoordinateCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class Coordinate implements Castable
{
    public const EARTH_RADIUS = 6371000;

    public function __construct(
        public float $lat,
        public float $lng
    ) {}

    public static function fromArray(array $data): self
    {

        $lat = $data['lat'] ?? $data['latitude'] ?? throw new InvalidArgumentException('Latitude is required');
        $lng = $data['lon'] ?? $data['long'] ?? $data['lng'] ?? $data['longitude'] ?? throw new InvalidArgumentException('Longitude is required');

        return new self($lat, $lng);
    }

    public static function isValidLatitude(mixed $value): bool
    {
        return is_numeric($value) && $value >= -90 && $value <= 90;
    }

    public static function isValidLongitude(mixed $value): bool
    {
        return is_numeric($value) && $value >= -180 && $value <= 180;
    }

    public static function castUsing(array $attributes): string
    {
        return CoordinateCast::class;
    }

    public function distanceTo(self $other): Meter
    {
        return Meter::fromValue(self::haversineGreatCircleDistance($this, $other));
    }

    private function toRad(): self
    {
        return new self(
            deg2rad($this->lat),
            deg2rad($this->lng)
        );
    }

    private static function haversineGreatCircleDistance(
        self $from,
        self $to
    ): float {

        $from = $from->toRad();
        $to = $to->toRad();

        $latDelta = $to->lat - $from->lat;
        $lngDelta = $to->lng - $from->lng;

        $angle = 2 * asin(sqrt(sin($latDelta / 2) ** 2 +
          cos($from->lat) * cos($to->lat) * sin($lngDelta / 2) ** 2));

        return $angle * self::EARTH_RADIUS;
    }
}
