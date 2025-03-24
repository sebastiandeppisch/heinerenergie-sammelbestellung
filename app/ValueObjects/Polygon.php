<?php

namespace App\ValueObjects;

use App\Casts\PolygonCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class Polygon implements Castable, JsonSerializable
{
    public function __construct(
        /** @var array<Coordinate> */
        public array $coordinates = []
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

        return new Coordinate(
            lat: $center[0],
            lng: $center[1],
        );
    }

    /**
     * Check if a coordinate point is inside this polygon
     */
    public function containsPoint(Coordinate $point): bool
    {
        // Implementation of point-in-polygon algorithm using Ray Casting
        $vertices = $this->coordinates;
        $vertexCount = count($vertices);

        if ($vertexCount < 3) {
            return false; // Not a valid polygon
        }

        // Ray casting algorithm
        $inside = false;
        $x = $point->lng;
        $y = $point->lat;

        // For each edge of the polygon
        for ($i = 0, $j = $vertexCount - 1; $i < $vertexCount; $j = $i++) {
            $xi = $vertices[$i][0];
            $yi = $vertices[$i][1];
            $xj = $vertices[$j][0];
            $yj = $vertices[$j][1];

            // Check if the ray intersects with the edge
            $intersect = (($yi > $y) != ($yj > $y)) &&
                ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            // Flip the inside flag if an intersection is found
            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
