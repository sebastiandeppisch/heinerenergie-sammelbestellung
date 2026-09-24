<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\MapPointCategory;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $image_path,
        public string $group_id,
        public string $group_name,
        public int $map_points_count = 0,
        public ?string $created_at = null,
        public bool $can_edit = false,
    ) {}

    /**
     * @param  bool  $canEdit  Whether the current user may change the category. Categories inherited from an ancestor group are read-only.
     */
    public static function fromModel(MapPointCategory $category, bool $canEdit = false): self
    {
        return new self(
            id: $category->uuid,
            name: $category->name,
            image_path: $category->image_path !== null ? asset('storage/'.$category->image_path) : null,
            group_id: $category->group->uuid,
            group_name: $category->group->name,
            map_points_count: $category->mapPoints_count ?? $category->mapPoints()->count(),
            created_at: $category->created_at?->toISOString(),
            can_edit: $canEdit,
        );
    }
}
