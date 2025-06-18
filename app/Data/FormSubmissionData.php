<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Illuminate\Support\Collection;
use App\Models\FormSubmission;
use Illuminate\Support\Carbon;

#[TypeScript]
class FormSubmissionData extends Data
{
    public function __construct(
        public string $id,
        public string $form_name,
        public ?string $form_subscription,
        #[DataCollectionOf(SubmissionFieldData::class)]
        public Collection $fields,
		public Carbon $submitted_at,
        public bool $seen,
    ) {
    }

    public static function fromModel(FormSubmission $model): self
    {
        return new self(
            id: $model->id,
            form_name: $model->form_name,
            form_subscription: $model->form_subscription,
			submitted_at: $model->submitted_at,
            seen: $model->seen,
            fields: $model->submissionFields->map(fn($field) => SubmissionFieldData::fromModel($field)),
        );
    }
}
