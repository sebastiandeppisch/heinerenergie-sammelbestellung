<?php

namespace App\Data;

use App\Models\ChecklistEntryField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ChecklistEntryFieldData extends Data
{
    public function __construct(
        public FormFieldData $field,
        /**
         * @var int|float|string|array<string>|null
         */
        public null|int|float|string|array $value,
    ) {}

    public static function fromModel(ChecklistEntryField $model): self
    {
        return new self(
            field: FormFieldData::fromChecklistEntryField($model),
            value: $model->value,
        );
    }
}
