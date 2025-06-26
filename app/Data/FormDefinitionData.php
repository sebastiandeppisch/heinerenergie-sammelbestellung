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
        public string $group_id
    ) {}

    public static function fromModel(FormDefinition $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            is_active: $model->is_active,
            fields: $model->fields->map(fn ($field) => FormFieldData::fromModel($field)),
            group_id: $model->group_id,
        );
    }
}
