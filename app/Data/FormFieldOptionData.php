<?php

namespace App\Data;

use App\Models\FormFieldOption;
use App\Models\SubmissionFieldOption;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormFieldOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public string $value,
        public int $sort_order,
        public bool $is_default,
        public bool $is_required
    ) {}

    public static function fromModel(FormFieldOption $model): self
    {
        return new self(
            id: $model->uuid,
            label: $model->label,
            value: $model->value,
            sort_order: $model->sort_order,
            is_default: $model->is_default,
            is_required: $model->is_required
        );
    }

    public static function fromSubmissionFieldOption(SubmissionFieldOption $model): self
    {
        return new self(
            id: $model->uuid,
            label: $model->label,
            value: $model->value,
            sort_order: $model->sort_order,
            is_default: $model->is_default,
            is_required: $model->is_required
        );
    }
}
