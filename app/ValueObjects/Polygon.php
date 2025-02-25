<?php

namespace App\ValueObjects;

use App\Casts\PolygonCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use JsonSerializable;

class Polygon implements Castable, JsonSerializable
{
    public function __construct(
        private array $coordinates = []
    ) {}

    public static function castUsing(array $attributes): string
    {
        return PolygonCast::class;
    }

    public static function fromJson(?string $json): ?self
    {
        if ($json === null) {
            return null;
        }
        $coordinates = json_decode($json, true);

        return empty($coordinates) ? null : new self($coordinates);
    }

    public function toJson(): ?string
    {
        return empty($this->coordinates) ? null : json_encode($this->coordinates);
    }

    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    public function jsonSerialize(): array
    {
        return $this->coordinates;
    }

    public function getCenter(): Coordinate
    {
        // this is not the actual center, but its good enough for now
        $center = collect(collect($this->coordinates)->reduce(function ($carry, $item) {
            return [
                $carry[0] + $item[0],
                $carry[1] + $item[1],
            ];
        }, [0, 0]))->map(fn ($item) => $item / count($this->coordinates))->toArray();

        return new Coordinate($center[0], $center[1]);
    }
}
