<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormDefinitionToMapPointField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormToMapPointFieldData extends Data
{
    public function __construct(
        /** The category field the value is stored in. */
        public string $target_field_id,
        /** The form field the value comes from. */
        public string $source_field_id,
    ) {}

    public static function fromModel(FormDefinitionToMapPointField $model): self
    {
        return new self(
            target_field_id: $model->targetField->uuid,
            source_field_id: $model->sourceField->uuid,
        );
    }
}
