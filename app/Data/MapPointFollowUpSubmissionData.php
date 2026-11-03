<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormSubmission;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A submission of a follow-up form that added to a point, for its admins.
 */
#[TypeScript]
class MapPointFollowUpSubmissionData extends Data
{
    public function __construct(
        public string $id,
        public string $form_name,
        public ?Carbon $submitted_at,
    ) {}

    public static function fromModel(FormSubmission $model): self
    {
        return new self(id: $model->uuid, form_name: $model->form_name, submitted_at: $model->submitted_at);
    }
}
