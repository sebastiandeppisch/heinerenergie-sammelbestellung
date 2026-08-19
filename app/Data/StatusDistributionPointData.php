<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StatusDistributionPointData extends Data
{
    public function __construct(
        public string $date,
        /** @var array<string, int> */
        #[LiteralTypeScriptType('Record<string, number>')]
        public array $statusCounts,
    ) {}
}
