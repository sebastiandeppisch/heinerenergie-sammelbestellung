<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PublicConsent;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Services\MapPointConsentService;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The consent and contact address of a point, for its admins only.
 */
#[TypeScript]
class MapPointConsentInfoData extends Data
{
    /**
     * @param  array<int, string>  $field_labels  the consented keys as readable labels
     */
    public function __construct(
        public PublicConsent $public_consent,
        public string $public_consent_label,
        public ?Carbon $public_consent_at,
        public ?string $public_consent_text,
        public array $field_labels,
        public ?string $contact_email,
        public ?string $submission_id,
    ) {}

    public static function fromModel(MapPoint $model): self
    {
        $submission = $model->pointable;

        return new self(
            public_consent: $model->public_consent,
            public_consent_label: $model->public_consent->label(),
            public_consent_at: $model->public_consent_at,
            public_consent_text: $model->public_consent_text,
            field_labels: app(MapPointConsentService::class)->labelsOf($model->public_consent_fields ?? []),
            contact_email: $model->contact_email,
            submission_id: $submission instanceof FormSubmission ? $submission->uuid : null,
        );
    }
}
