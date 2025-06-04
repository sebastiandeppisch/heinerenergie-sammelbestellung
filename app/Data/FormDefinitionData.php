<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use App\Data\FormFieldData;
use App\Models\FormDefinition;
use Illuminate\Support\Collection;

#[TypeScript]
class FormDefinitionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public bool $is_active = true,
        #[DataCollectionOf(FormFieldData::class)]
        public Collection $fields,
    ) {
    }

    public static function fromModel(FormDefinition $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            is_active: $model->is_active,
            fields: $model->fields->map(fn($field) => FormFieldData::fromModel($field))
        );
    }
}
