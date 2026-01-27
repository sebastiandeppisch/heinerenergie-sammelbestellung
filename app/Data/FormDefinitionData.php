<?php

namespace App\Data;

use App\Models\FormDefinition;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormDefinitionData extends Data
{
    /**
     * @param  Collection<FormFieldData>  $fields
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public bool $is_active,
        #[DataCollectionOf(FormFieldData::class)]
        public Collection $fields,
        public string $group_id,
        public ?FormToAdviceMappingData $advice_mapping = null,
        public ?FormToMapPointMappingData $map_point_mapping = null,
        public ?string $success_message = null,
        public bool $show_next_form_button = false,
        public ?string $next_form_button_text = null,
    ) {}

    public static function fromModel(FormDefinition $model): self
    {
        $model->loadMissing('group');

        return new self(
            id: $model->uuid,
            name: $model->name,
            description: $model->description,
            is_active: $model->is_active,
            fields: $model->fields->map(fn ($field) => FormFieldData::fromModel($field)),
            group_id: $model->group->uuid,
            advice_mapping: FormToAdviceMappingData::fromModel($model->adviceCreator),
            map_point_mapping: FormToMapPointMappingData::fromModel($model->mapPointCreator),
            success_message: $model->success_message,
            show_next_form_button: $model->show_next_form_button ?? false,
            next_form_button_text: $model->next_form_button_text,
        );
    }
}
