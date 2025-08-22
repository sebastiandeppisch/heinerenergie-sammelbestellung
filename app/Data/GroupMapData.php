<?php

namespace App\Data;

use App\Models\Group;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use InvalidArgumentException;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GroupMapData extends Data
{
    public function __construct(
        public Polygon $polygon,
        public Coordinate $center,
        public string $name,
        public ?string $logo_path,
    ) {}

    public static function fromModel(Group $group): self
    {

        if ($group->consulting_area === null) {
            throw new InvalidArgumentException('Can only create map data from group with consulting area');
        }

        return new self(
            polygon: $group->consulting_area,
            center: $group->consulting_area->getCenter(),
            name: $group->name,
            logo_path: $group->full_logo_path ? url($group->full_logo_path) : null,
        );
    }
}
