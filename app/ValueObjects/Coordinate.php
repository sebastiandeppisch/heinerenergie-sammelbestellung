<?php

namespace App\ValueObjects;

use App\Casts\Coordinate as CoordinateCast;
use Illuminate\Contracts\Database\Eloquent\Castable;

class Coordinate implements Castable
{
    public const EARTH_RADIUS = 6371000;

    public function __construct(
        public float $lat,
        public float $long
    ) {}

    public static function fromArray(array $data): self
    {
        $lat = $data['lat'];
        $long = $data['lon'] ?? $data['long'];

        return new self($lat, $long);
    }

    public static function castUsing(array $attributes): string
    {
        return CoordinateCast::class;
    }

    public function distanceTo(self $other): Meter
    {
        return Meter::fromValue($this->haversineGreatCircleDistance(
            $this,
            $other
        ));
    }

    private function toRad(): self
    {
        return new self(
            deg2rad($this->lat),
            deg2rad($this->long)
        );
    }

    private static function haversineGreatCircleDistance(
        self $from,
        self $to
    ): float {

        $from = $from->toRad();
        $to = $to->toRad();

        $latDelta = $to->lat - $from->lat;
        $lonDelta = $to->long - $from->long;

        $angle = 2 * asin(sqrt(sin($latDelta / 2) ** 2 +
          cos($from->lat) * cos($to->lat) * sin($lonDelta / 2) ** 2));

        return $angle * self::EARTH_RADIUS;
    }
}
