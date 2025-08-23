<?php

namespace App\Data;

use App\Models\Category;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $image_path,
        public int $map_points_count = 0,
        public ?string $created_at = null,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->uuid,
            name: $category->name,
            image_path: $category->image_path,
            map_points_count: $category->mapPoints_count ?? $category->mapPoints()->count(),
            created_at: $category->created_at?->toISOString(),
        );
    }
}
