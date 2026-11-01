<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormSubmission;
use App\Models\SubmissionField;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormSubmissionData extends Data
{
    /**
     * @param  Collection<int, SubmissionFieldData>  $fields
     */
    public function __construct(
        public string $id,
        public string $form_name,
        #[DataCollectionOf(SubmissionFieldData::class)]
        public Collection $fields,
        public Carbon $submitted_at,
        public bool $seen,
        public bool $awaiting_confirmation = false,
        public bool $targets_failed = false,
        /** E.g. „Kartenpunkt am 01.11.2026 von einem Admin gelöscht“, when the point the submission created is gone. */
        public ?string $map_point_deleted = null,
    ) {}

    public static function fromModel(FormSubmission $model): self
    {
        return new self(
            id: $model->uuid,
            form_name: $model->form_name,
            submitted_at: $model->submitted_at,
            seen: $model->seen,
            awaiting_confirmation: $model->isAwaitingConfirmation(),
            targets_failed: $model->targets_failed_at !== null,
            map_point_deleted: $model->map_point_deleted_at === null || $model->map_point_deleted_by === null
                ? null
                : 'Kartenpunkt am '.$model->map_point_deleted_at->timezone(config('app.timezone'))->format('d.m.Y').' '.$model->map_point_deleted_by->label().' gelöscht',
            fields: $model->submissionFields->map(fn (SubmissionField $field): SubmissionFieldData => SubmissionFieldData::fromModel($field)),
        );
    }
}
