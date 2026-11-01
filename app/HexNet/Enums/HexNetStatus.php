<?php

declare(strict_types=1);

namespace App\HexNet\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum HexNetStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Entwurf',
            self::ACTIVE => 'Läuft',
        };
    }
}
