<?php

declare(strict_types=1);

namespace App\HexNet\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What an admin can do with a new stepping stone right from the mail. Withdrawing only hides it, it still counts.
 */
#[TypeScript]
enum HexNetModerationAction: string
{
    case PUBLISH = 'freigeben';
    case WITHDRAW = 'zurueckziehen';
    case DELETE = 'loeschen';

    public function label(): string
    {
        return match ($this) {
            self::PUBLISH => 'Freigeben',
            self::WITHDRAW => 'Zurückziehen',
            self::DELETE => 'Löschen',
        };
    }
}
