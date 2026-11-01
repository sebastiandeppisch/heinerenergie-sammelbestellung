<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;

/**
 * Pointy-top axial hexagon grid on a local equirectangular projection around the origin. Cell ids are "q:r".
 */
final readonly class HexGrid
{
    public const float EARTH_RADIUS = 6378137.0;

    private float $cosLat;

    public function __construct(public Coordinate $origin, public float $radius)
    {
        $this->cosLat = cos(deg2rad($origin->lat));
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function project(Coordinate $coordinate): array
    {
        return [
            deg2rad($coordinate->lng - $this->origin->lng) * self::EARTH_RADIUS * $this->cosLat,
            deg2rad($coordinate->lat - $this->origin->lat) * self::EARTH_RADIUS,
        ];
    }

    public function unproject(float $x, float $y): Coordinate
    {
        return new Coordinate(
            $this->origin->lat + rad2deg($y / self::EARTH_RADIUS),
            $this->origin->lng + rad2deg($x / (self::EARTH_RADIUS * $this->cosLat)),
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function axialOf(Coordinate $coordinate): array
    {
        [$x, $y] = $this->project($coordinate);

        return self::round((sqrt(3) / 3 * $x - $y / 3) / $this->radius, (2 / 3 * $y) / $this->radius);
    }

    public function cellOf(Coordinate $coordinate): string
    {
        return self::id(...$this->axialOf($coordinate));
    }

    public static function id(int $q, int $r): string
    {
        return $q.':'.$r;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function parseId(string $id): array
    {
        [$q, $r] = array_map(intval(...), explode(':', $id, 2));

        return [$q, $r];
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function centerXY(int $q, int $r): array
    {
        return [$this->radius * sqrt(3) * ($q + $r / 2), $this->radius * 1.5 * $r];
    }

    public function center(int $q, int $r): Coordinate
    {
        return $this->unproject(...$this->centerXY($q, $r));
    }

    /**
     * @return array<int, Coordinate>
     */
    public function corners(int $q, int $r): array
    {
        [$cx, $cy] = $this->centerXY($q, $r);
        $corners = [];

        for ($i = 0; $i < 6; $i++) {
            $angle = deg2rad(60 * $i - 30);
            $corners[] = $this->unproject($cx + $this->radius * cos($angle), $cy + $this->radius * sin($angle));
        }

        return $corners;
    }

    /**
     * Area of one cell in hectares.
     */
    public function cellAreaHa(): float
    {
        return 3 * sqrt(3) / 2 * $this->radius ** 2 / 10000;
    }

    /**
     * Ids of all cells whose center lies inside the polygon.
     *
     * @return array<int, string>
     */
    public function cellsInPolygon(Polygon $polygon): array
    {
        $ring = array_map($this->project(...), $polygon->coordinates);

        if (count($ring) < 3) {
            return [];
        }

        $xs = array_column($ring, 0);
        $ys = array_column($ring, 1);
        [$minX, $maxX, $minY, $maxY] = [min($xs), max($xs), min($ys), max($ys)];

        $rMin = (int) floor((2 / 3 * $minY) / $this->radius) - 1;
        $rMax = (int) ceil((2 / 3 * $maxY) / $this->radius) + 1;
        $cells = [];

        for ($r = $rMin; $r <= $rMax; $r++) {
            $qMin = (int) floor($minX / ($this->radius * sqrt(3)) - $r / 2) - 1;
            $qMax = (int) ceil($maxX / ($this->radius * sqrt(3)) - $r / 2) + 1;

            for ($q = $qMin; $q <= $qMax; $q++) {
                [$x, $y] = $this->centerXY($q, $r);

                if (self::inRing($x, $y, $ring)) {
                    $cells[] = self::id($q, $r);
                }
            }
        }

        return $cells;
    }

    public function contains(Polygon $polygon, Coordinate $coordinate): bool
    {
        [$x, $y] = $this->project($coordinate);

        return self::inRing($x, $y, array_map($this->project(...), $polygon->coordinates));
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    private static function inRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function round(float $qf, float $rf): array
    {
        $sf = -$qf - $rf;
        $q = (int) round($qf);
        $r = (int) round($rf);
        $s = (int) round($sf);
        $dq = abs($q - $qf);
        $dr = abs($r - $rf);
        $ds = abs($s - $sf);

        if ($dq > $dr && $dq > $ds) {
            $q = -$r - $s;
        } elseif ($dr > $ds) {
            $r = -$q - $s;
        }

        return [$q, $r];
    }
}
