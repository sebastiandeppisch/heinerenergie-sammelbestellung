<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PublicConsent;
use App\Models\MapPoint;
use App\Services\MapPointConsentService;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The point as its person sees it on the manage page.
 */
#[TypeScript]
class MapPointManageData extends Data
{
    /**
     * @param  array<int, string>  $characteristics
     * @param  array<int, string>  $consented_labels
     * @param  array<int, string>  $offered_labels  the details the second level would show now
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $category,
        public array $characteristics,
        public bool $published,
        public PublicConsent $public_consent,
        public string $public_consent_label,
        public ?Carbon $public_consent_at,
        public bool $shows_details,
        public array $consented_labels,
        public string $consent_text,
        public array $offered_labels,
    ) {}

    public static function fromModel(MapPoint $model): self
    {
        $model->loadMissing(['category', 'characteristics']);
        $consents = app(MapPointConsentService::class);
        $preview = $consents->previewForPoint($model);
        $fields = $model->public_consent_fields ?? [];

        return new self(
            title: $model->title,
            description: $model->description ?? '',
            category: $model->category?->name,
            characteristics: $model->characteristics->pluck('name')->all(),
            published: $model->published,
            public_consent: $model->public_consent,
            public_consent_label: $model->public_consent->label(),
            public_consent_at: $model->public_consent_at,
            shows_details: $model->public_consent === PublicConsent::GIVEN && array_diff($fields, [MapPointConsentService::COORDINATE]) !== [],
            consented_labels: $model->public_consent === PublicConsent::GIVEN ? $consents->labelsOf($fields) : [],
            consent_text: $preview['text'],
            offered_labels: $preview['labels'],
        );
    }
}
