<?php

namespace App\ValueObjects;

use JsonSerializable;

class Polygon implements JsonSerializable
{
    public function __construct(
        private array $coordinates = []
    ) {}

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
}
