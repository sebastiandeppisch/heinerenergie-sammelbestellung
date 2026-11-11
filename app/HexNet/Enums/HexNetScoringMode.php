<?php

declare(strict_types=1);

namespace App\HexNet\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What a cell counts to reach its level.
 */
#[TypeScript]
enum HexNetScoringMode: string
{
    /** Stepping stones in the cell. */
    case STONES = 'stones';

    /** Measures of all stepping stones in the cell, a stone with three measures counts three. */
    case MEASURES = 'measures';

    /** Different measures in the cell, the variety. */
    case DISTINCT_MEASURES = 'distinct_measures';

    public function label(): string
    {
        return match ($this) {
            self::STONES => 'Trittsteine',
            self::MEASURES => 'Maßnahmen',
            self::DISTINCT_MEASURES => 'Verschiedene Maßnahmen',
        };
    }
}
