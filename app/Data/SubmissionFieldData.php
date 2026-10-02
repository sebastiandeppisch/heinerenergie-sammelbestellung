<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FieldType;
use App\Models\SubmissionField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SubmissionFieldData extends Data
{
    public function __construct(
        /**
         * @var int|float|string|array<string>|null
         */
        public null|int|float|string|array $value,
        public FormFieldData $field,

    ) {}

    /**
     * Images are given as urls of the route that checks access, never as paths on the disk.
     */
    public static function fromModel(SubmissionField $model): self
    {
        $value = $model->value;

        if ($model->type === FieldType::IMAGE && is_array($value)) {
            $value = array_map(
                fn (string $path): string => route('form-images.show', [basename(dirname($path)), basename($path)]),
                array_values(array_filter($value, is_string(...))),
            );
        }

        return new self(
            value: $value,
            field: FormFieldData::fromSubmissionField($model)
        );
    }
}
