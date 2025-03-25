<?php

namespace App\ValueObjects;

use JsonSerializable;
use App\Casts\PolygonCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Polygon implements Castable, JsonSerializable
{
    public function __construct(
        /** @var array<Coordinate> */
        readonly private array $coordinates = []
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
        $data = json_decode($json, true);

        if (array_key_exists('coordinates', $data)) {
            $coordinates = $data['coordinates'];
        } else {
            $coordinates = $data;
        }

        $coordinates = array_map(fn ($coordinate) => Coordinate::fromArray($coordinate), $coordinates);

        return empty($coordinates) ? null : new self($coordinates);
    }

    public function toJson(): ?string
    {
        return empty($this->coordinates) ? null : json_encode($this->jsonSerialize());
    }

    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    public function jsonSerialize(): ?array
    {
        if(empty($this->coordinates) || count($this->coordinates) === 0 ) {
            return null;
        }

        return [
            'coordinates' => $this->coordinates,
        ];
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
