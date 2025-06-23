<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use App\Models\FormFieldOption;

#[TypeScript]
class FormFieldOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public string $value,
        public int $sort_order = 0,
        public bool $is_default = false,
    ) {
    }

    public static function fromModel(FormFieldOption $model): self
    {
        return new self(
            id: $model->id,
            label: $model->label,
            value: $model->value,
            sort_order: $model->sort_order,
            is_default: $model->is_default,
        );
    }
}
