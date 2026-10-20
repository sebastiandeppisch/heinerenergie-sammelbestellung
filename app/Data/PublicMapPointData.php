<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\MapPoint;
use App\ValueObjects\Coordinate;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What visitors of a public map may see of a point. Never carries the contact address.
 */
#[TypeScript]
class PublicMapPointData extends Data
{
    public function __construct(
        public string $id,
        public Coordinate $coordinate,
        public string $title,
        public string $description,
        public ?string $category_id,
        public ?string $location,
        public bool $has_contact,
    ) {}

    public static function fromModel(MapPoint $model): self
    {
        return new self(
            id: $model->uuid,
            coordinate: $model->coordinate,
            title: $model->title,
            description: $model->description ?? '',
            category_id: $model->category?->uuid,
            location: $model->location,
            has_contact: $model->hasContact(),
        );
    }
}
