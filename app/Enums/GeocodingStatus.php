<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Tracks why an advice does or does not have coordinates.
 *
 * Without this a missing coordinate is ambiguous: the lookup may still be
 * running, OpenStreetMap may not know the address, or the service may have been
 * down. Only PENDING is ever picked up again by the geocoding job.
 */
#[TypeScript]
enum GeocodingStatus: string
{
    /** The lookup has been queued but has not finished yet. */
    case PENDING = 'pending';

    /** OpenStreetMap returned coordinates. */
    case SUCCESS = 'success';

    /** OpenStreetMap does not know this address. The user has to place the pin. */
    case NOT_FOUND = 'not_found';

    /** OpenStreetMap could not be reached and all retries were used up. */
    case FAILED = 'failed';

    /** Somebody placed the pin by hand. Never overwrite this. */
    case MANUAL = 'manual';

    /**
     * Whether the geocoding job may still write to this advice.
     */
    public function isOpen(): bool
    {
        return $this === self::PENDING;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Koordinaten werden ermittelt',
            self::SUCCESS => 'Koordinaten ermittelt',
            self::NOT_FOUND => 'Adresse nicht gefunden',
            self::FAILED => 'Adressdienst nicht erreichbar',
            self::MANUAL => 'Position von Hand gesetzt',
        };
    }
}
