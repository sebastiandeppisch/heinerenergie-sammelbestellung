<?php

namespace App\Data;

use App\Models\FormSubmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormSubmissionData extends Data
{
    public function __construct(
        public string $id,
        public string $form_name,
        #[DataCollectionOf(SubmissionFieldData::class)]
        public Collection $fields,
        public Carbon $submitted_at,
        public bool $seen,
    ) {}

    public static function fromModel(FormSubmission $model): self
    {
        return new self(
            id: $model->uuid,
            form_name: $model->form_name,
            submitted_at: $model->submitted_at,
            seen: $model->seen,
            fields: $model->submissionFields->map(fn ($field) => SubmissionFieldData::fromModel($field)),
        );
    }
}
