<?php

namespace App\Data;

use App\Models\ChecklistEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ChecklistEntryData extends Data
{
    /**
     * @param  Collection<int, ChecklistEntryFieldData>  $fields
     */
    public function __construct(
        public string $id,
        public FormDefinitionData $form_definition,
        #[DataCollectionOf(ChecklistEntryFieldData::class)]
        public Collection $fields,
        public Carbon $updated_at,
    ) {}

    public static function fromModel(ChecklistEntry $model): self
    {
        $model->loadMissing('fields.options');

        return new self(
            id: $model->uuid,
            form_definition: FormDefinitionData::fromModel($model->formDefinition),
            fields: $model->fields->map(fn ($field) => ChecklistEntryFieldData::fromModel($field)),
            updated_at: $model->updated_at,
        );
    }
}
