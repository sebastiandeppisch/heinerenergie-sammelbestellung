<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * An own field of a characteristic, with whether it is shown on the public map.
 */
#[TypeScript]
class MapPointCharacteristicFieldData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $is_public,
    ) {}
}
