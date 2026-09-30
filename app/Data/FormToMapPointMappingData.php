<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormDefinitionToMapPoint;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormToMapPointMappingData extends Data
{
    /**
     * @param  Collection<int, FormToMapPointSubcategoryData>  $subcategory_options
     * @param  Collection<int, FormToMapPointFieldData>  $field_mappings
     */
    public function __construct(
        public bool $enabled,
        public ?string $title_field_id = null,
        public ?string $description_field_id = null,
        public ?string $coordinate_field_id = null,
        public ?string $category_id = null,
        public ?string $subcategory_field_id = null,
        #[DataCollectionOf(FormToMapPointSubcategoryData::class)]
        public Collection $subcategory_options = new Collection,
        #[DataCollectionOf(FormToMapPointFieldData::class)]
        public Collection $field_mappings = new Collection,
    ) {}

    public static function fromModel(?FormDefinitionToMapPoint $model): self
    {
        if ($model === null) {
            return new self(enabled: false);
        }

        $model->loadMissing(['titleField', 'descriptionField', 'coordinateField', 'category', 'subcategoryField', 'subcategories.category', 'fieldMappings.targetField', 'fieldMappings.sourceField']);

        return new self(
            enabled: true,
            title_field_id: $model->titleField?->uuid,
            description_field_id: $model->descriptionField?->uuid,
            coordinate_field_id: $model->coordinateField?->uuid,
            category_id: $model->category?->uuid,
            subcategory_field_id: $model->subcategoryField?->uuid,
            subcategory_options: $model->subcategories->map(FormToMapPointSubcategoryData::fromModel(...))->toBase(),
            field_mappings: $model->fieldMappings->map(FormToMapPointFieldData::fromModel(...))->toBase(),
        );
    }
}
