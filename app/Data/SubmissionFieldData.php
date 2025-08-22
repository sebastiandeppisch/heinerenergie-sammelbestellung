<?php

namespace App\Data;

use App\Models\SubmissionField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SubmissionFieldData extends Data
{
    public function __construct(
        /**
         * @var int|float|string|array<string>
         */
        public null|int|float|string|array $value,
        public FormFieldData $field,

    ) {}

    public static function fromModel(SubmissionField $model): self
    {
        return new self(
            value: $model->value,
            field: FormFieldData::fromSubmissionField($model)
        );
    }
}
