<?php

namespace App\ValueObjects;

use App\Casts\Coordinate as CoordinateCast;
use Illuminate\Contracts\Database\Eloquent\Castable;

class Coordinate implements Castable
{
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

    public function distanceTo(self $coordinate): Meter
    {
        return Meter::fromValue($this->haversineGreatCircleDistance(
            $this->lat,
            $this->long,
            $coordinate->lat,
            $coordinate->long
        ));
    }

    private function haversineGreatCircleDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000): float
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(sin($latDelta / 2) ** 2 +
          cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2));

        return $angle * $earthRadius;
    }
}
