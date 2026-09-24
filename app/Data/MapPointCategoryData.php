<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\MapPointCategory;
use App\ValueObjects\MapPointCategoryTree;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $image_path,
        public ?string $marker_image_path,
        public ?string $parent_id,
        public string $group_id,
        public string $group_name,
        public int $map_points_count = 0,
        public ?string $created_at = null,
        public bool $can_edit = false,
    ) {}

    /**
     * @param  bool  $canEdit  Whether the current user may change the category. Categories inherited from an ancestor group are read-only.
     * @param  MapPointCategoryTree|null  $tree  Pass a loaded tree when converting many categories, otherwise it is loaded for each one.
     */
    public static function fromModel(MapPointCategory $category, bool $canEdit = false, ?MapPointCategoryTree $tree = null): self
    {
        $tree ??= MapPointCategory::tree();
        $markerImagePath = $tree->markerImagePath($category->id);

        return new self(
            id: $category->uuid,
            name: $category->name,
            image_path: $category->image_path !== null ? asset('storage/'.$category->image_path) : null,
            marker_image_path: $markerImagePath !== null ? asset('storage/'.$markerImagePath) : null,
            parent_id: $tree->parentUuid($category->id),
            group_id: $category->group->uuid,
            group_name: $category->group->name,
            map_points_count: $category->mapPoints_count ?? $category->mapPoints()->count(),
            created_at: $category->created_at?->toISOString(),
            can_edit: $canEdit,
        );
    }
}
