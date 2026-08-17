<?php

namespace App\Data;

use App\Models\MapEmbed;
use App\Models\MapPointCategory;
use App\ValueObjects\Coordinate;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapEmbedData extends Data
{
    /**
     * @param  array<int, MapPointCategoryData>  $categories
     */
    public function __construct(
        public string $id,
        public ?string $name,
        public array $categories,
        public Coordinate $coordinate,
        public int $zoom,
        public bool $show_table,
        public ?string $created_at = null,
    ) {}

    public static function fromModel(MapEmbed $model): self
    {
        return new self(
            id: $model->uuid,
            name: $model->name,
            categories: $model->mapPointCategories->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category))->all(),
            coordinate: $model->coordinate,
            zoom: $model->zoom,
            show_table: $model->show_table,
            created_at: $model->created_at?->toISOString(),
        );
    }
}
