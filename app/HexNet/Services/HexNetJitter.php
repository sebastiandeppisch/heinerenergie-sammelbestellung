<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\ValueObjects\Coordinate;

/**
 * Moves a point by a fixed offset derived from HMAC(uuid, app.key), so the same point always lands on the same
 * public position and the exact address cannot be read from it.
 */
class HexNetJitter
{
    public function __construct(private readonly string $key) {}

    public function apply(Coordinate $coordinate, string $uuid, int $maxMeters): Coordinate
    {
        if ($maxMeters <= 0) {
            return $coordinate;
        }

        [$dx, $dy] = $this->offset($uuid, $maxMeters);
        $grid = new HexGrid($coordinate, 1);

        return $grid->unproject($dx, $dy);
    }

    /**
     * @return array{0: float, 1: float} offset in meters (east, north)
     */
    public function offset(string $uuid, int $maxMeters): array
    {
        $hash = hash_hmac('sha256', $uuid, $this->key, true);
        $angle = $this->unit(substr($hash, 0, 4)) * 2 * M_PI;
        $length = sqrt($this->unit(substr($hash, 4, 4))) * $maxMeters;

        return [cos($angle) * $length, sin($angle) * $length];
    }

    private function unit(string $bytes): float
    {
        /** @var array{1: int} $value */
        $value = unpack('N', $bytes);

        return $value[1] / 4294967296;
    }
}
