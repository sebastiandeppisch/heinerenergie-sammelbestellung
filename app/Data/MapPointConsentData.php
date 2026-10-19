<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PublicConsent;
use Spatie\LaravelData\Data;

/**
 * A consent as it is stored on a map point.
 */
class MapPointConsentData extends Data
{
    /**
     * @param  array<int, string>|null  $fields  the consented keys, see MapPointConsentService
     */
    public function __construct(
        public PublicConsent $consent,
        public ?string $text = null,
        public ?array $fields = null,
    ) {}
}
