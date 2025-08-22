<?php

namespace App\Data;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\SubmissionField;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormFieldData extends Data
{
    public function __construct(
        public string $id,
        public FieldType $type,
        public string $label,
        #[DataCollectionOf(FormFieldOptionData::class)]
        public Collection $options,
        public ?string $placeholder = null,
        public ?string $help_text = null,
        public bool $required = false,
        public ?string $default_value = null,
        public int $sort_order = 0,
        public ?int $min_length = null,
        public ?int $max_length = null,
        public ?float $min_value = null,
        public ?float $max_value = null,
        public ?array $accepted_file_types = null,
    ) {}

    public static function fromModel(FormField $model): self
    {
        $model->loadMissing('options');

        return new self(
            id: $model->uuid,
            type: $model->type,
            label: $model->label,
            placeholder: $model->placeholder,
            help_text: $model->help_text,
            required: $model->required,
            default_value: $model->default_value,
            sort_order: $model->sort_order,
            min_length: $model->min_length,
            max_length: $model->max_length,
            min_value: $model->min_value,
            max_value: $model->max_value,
            accepted_file_types: $model->accepted_file_types,
            options: $model->options->map(fn ($option) => FormFieldOptionData::fromModel($option)),
        );
    }

    public static function fromSubmissionField(SubmissionField $model): self
    {
        return new self(
            id: $model->uuid,
            type: $model->type,
            label: $model->label,
            required: false,
            options: $model->options->map(fn ($option) => FormFieldOptionData::fromSubmissionFieldOption($option)),
            placeholder: null
        );
    }
}
