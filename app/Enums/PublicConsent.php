<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Whether the person behind a map point allowed it to be public. The admin's approval is the separate flag published,
 * a point is public only when both allow it.
 */
#[TypeScript]
enum PublicConsent: string
{
    /** Points created by admins, imported or existing before consents were asked for. */
    case NOT_REQUIRED = 'not_required';

    /** The person allowed the point and the keys in public_consent_fields to be public. */
    case GIVEN = 'given';

    /** The person refused or withdrew the consent. The point is never public. */
    case REFUSED = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::NOT_REQUIRED => 'Nicht erforderlich',
            self::GIVEN => 'Erteilt',
            self::REFUSED => 'Verweigert',
        };
    }
}
