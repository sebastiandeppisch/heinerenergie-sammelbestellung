<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\MapPoint;
use App\ValueObjects\Coordinate;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What visitors of a public map may see of a point. Never carries the contact address.
 */
#[TypeScript]
class PublicMapPointData extends Data
{
    /**
     * @param  Collection<int, MapPointFieldValueData>  $fields  only the public values
     * @param  Collection<int, MapPointCharacteristicData>  $characteristics
     */
    public function __construct(
        public string $id,
        public Coordinate $coordinate,
        public string $title,
        public string $description,
        public ?string $category_id,
        public ?string $location,
        #[DataCollectionOf(MapPointFieldValueData::class)]
        public Collection $fields,
        #[DataCollectionOf(MapPointCharacteristicData::class)]
        public Collection $characteristics,
        public bool $has_contact,
    ) {}

    /**
     * @param  array<int, int>|null  $publicFieldIds  see MapPointData::fromModel()
     */
    public static function fromModel(MapPoint $model, ?MapPointCategoryTree $tree = null, ?array $publicFieldIds = null): self
    {
        $point = MapPointData::fromModel($model, true, $tree, $publicFieldIds);

        return new self(
            id: $point->id,
            coordinate: $point->coordinate,
            title: $point->title,
            description: $point->description,
            category_id: $point->category_id,
            location: $point->location,
            fields: $point->fields,
            characteristics: $point->characteristics,
            has_contact: $model->hasContact(),
        );
    }
}
