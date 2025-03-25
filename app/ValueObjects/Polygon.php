<?php

namespace App\ValueObjects;

use App\Casts\PolygonCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Polygon implements Castable, JsonSerializable
{
    /** @var array<Coordinate> */
    public readonly array $coordinates;

    public function __construct(
        /** @var array<Coordinate|array<int>> */
        array $coordinates = []
    ) {
        $this->coordinates = array_map(fn ($coordinate) => $coordinate instanceof Coordinate ? $coordinate : Coordinate::fromArray($coordinate), $coordinates);
    }

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
        if (empty($this->coordinates) || count($this->coordinates) === 0) {
            return null;
        }

        return [
            'coordinates' => $this->coordinates,
        ];
    }

    public function getCenter(): Coordinate
    {
        $zeroCoordinate = new Coordinate(0, 0);

        // this is not the actual center, but its good enough for now
        $center = collect($this->coordinates)->reduce(function (Coordinate $carry, Coordinate $item): Coordinate {

            return new Coordinate(
                lat: $carry->lat + $item->lat,
                lng: $carry->lng + $item->lng,
            );
        }, $zeroCoordinate);

        $divider = count($this->coordinates);

        return new Coordinate(
            lat: $center->lat / $divider,
            lng: $center->lng / $divider,
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
            $xi = $vertices[$i]->lng;
            $yi = $vertices[$i]->lat;
            $xj = $vertices[$j]->lng;
            $yj = $vertices[$j]->lat;

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
