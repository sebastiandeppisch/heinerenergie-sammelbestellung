<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MapPointTargetMode;
use App\Models\FormDefinitionToMapPoint;
use App\Services\MapPointConsentService;
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
     * @param  Collection<int, FormToMapPointCharacteristicData>  $characteristic_options
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
        /** A checkbox field of the form whose options pick characteristics. */
        public ?string $characteristics_field_id = null,
        #[DataCollectionOf(FormToMapPointCharacteristicData::class)]
        public Collection $characteristic_options = new Collection,
        public ?string $contact_email_field_id = null,
        public ?string $contact_enabled_field_id = null,
        /** The consent text of the second level, with the placeholders {initiative}, {felder} and {widerruf}. */
        public ?string $consent_text = null,
        /** The consent text of the first level, with the placeholders {initiative} and {widerruf}. */
        public ?string $consent_point_text = null,
        /** A text field whose value becomes the location description of the point. */
        public ?string $location_field_id = null,
        public MapPointTargetMode $mode = MapPointTargetMode::CREATE,
        /** A form with an update target that adds to the created points, offered after submitting and on the manage page. */
        public ?string $follow_up_form_definition_id = null,
        public ?string $follow_up_button_text = null,
    ) {}

    public static function fromModel(?FormDefinitionToMapPoint $model): self
    {
        if ($model === null) {
            return new self(enabled: false, consent_text: MapPointConsentService::DEFAULT_DETAILS_TEXT, consent_point_text: MapPointConsentService::DEFAULT_POINT_TEXT);
        }

        $model->loadMissing(['titleField', 'descriptionField', 'coordinateField', 'category', 'subcategoryField', 'subcategories.category', 'fieldMappings.targetField', 'fieldMappings.sourceField', 'characteristicsField', 'characteristicMappings.characteristic', 'contactEmailField', 'contactEnabledField', 'locationField', 'followUpFormDefinition']);

        return new self(
            enabled: true,
            title_field_id: $model->titleField?->uuid,
            description_field_id: $model->descriptionField?->uuid,
            coordinate_field_id: $model->coordinateField?->uuid,
            category_id: $model->category?->uuid,
            subcategory_field_id: $model->subcategoryField?->uuid,
            subcategory_options: $model->subcategories->map(FormToMapPointSubcategoryData::fromModel(...))->toBase(),
            field_mappings: $model->fieldMappings->map(FormToMapPointFieldData::fromModel(...))->toBase(),
            characteristics_field_id: $model->characteristicsField?->uuid,
            characteristic_options: $model->characteristicMappings->map(FormToMapPointCharacteristicData::fromModel(...))->toBase(),
            contact_email_field_id: $model->contactEmailField?->uuid,
            contact_enabled_field_id: $model->contactEnabledField?->uuid,
            consent_text: $model->consent_text,
            consent_point_text: $model->consent_point_text,
            location_field_id: $model->locationField?->uuid,
            mode: $model->mode,
            follow_up_form_definition_id: $model->followUpFormDefinition?->uuid,
            follow_up_button_text: $model->follow_up_button_text,
        );
    }
}
