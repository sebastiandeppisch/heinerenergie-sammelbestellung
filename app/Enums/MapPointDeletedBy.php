<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Who deleted the map point a form submission created, so the submission shows why its point is gone.
 */
#[TypeScript]
enum MapPointDeletedBy: string
{
    /** The person behind the point, on the manage page. */
    case PERSON = 'person';

    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::PERSON => 'von der eintragenden Person',
            self::ADMIN => 'von einem Admin',
        };
    }
}
