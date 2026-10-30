<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PublicConsent;
use Spatie\LaravelData\Data;

/**
 * The map point a submission creates, read from the form when it was submitted.
 */
class MapPointTargetPayload extends Data
{
    /**
     * @param  array<string, mixed>  $field_values  keyed by the uuid of the field of the category or a characteristic, images as their paths
     * @param  array<int, int>  $characteristic_ids  picked by the checked options of the characteristics field
     * @param  PublicConsent  $public_consent  payloads frozen before consents were asked for have none, their points stay as before
     * @param  array<int, string>|null  $public_consent_fields  the consented keys, see MapPointConsentService
     */
    public function __construct(
        public mixed $title,
        public mixed $description,
        public mixed $coordinate,
        public ?int $category_id,
        public int $group_id,
        public array $field_values,
        public array $characteristic_ids = [],
        public ?string $contact_email = null,
        public bool $contact_enabled = false,
        public PublicConsent $public_consent = PublicConsent::NOT_REQUIRED,
        public ?string $public_consent_text = null,
        public ?array $public_consent_fields = null,
    ) {}
}
