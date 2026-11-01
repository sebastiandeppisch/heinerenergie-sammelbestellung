<?php

declare(strict_types=1);

namespace App\HexNet\Data;

use App\Data\PublicMapPointData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A visible point of a HexaNetz. Its coordinate is already moved by the jitter.
 */
#[TypeScript]
class HexNetPointData extends Data
{
    public function __construct(
        public PublicMapPointData $point,
        public int $since,
    ) {}
}
